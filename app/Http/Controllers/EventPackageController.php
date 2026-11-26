<?php

/**
 * @empresa Bigbitbyte
 *
 * @autor Ivan Hernandez Hinojosa
 *
 * @creado 14/noviembre/2026
 *
 * @modificado 24/noviembre/2026
 *
 * @descripción Recomienda paquetes y guarda la selección de proveedores de un evento.
 */

namespace App\Http\Controllers;

use App\Models\Package;
use App\Models\Provider;
use App\Models\User;
use App\Services\BudgetService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EventPackageController extends Controller
{
    /**
     * Devuelve los paquetes del tipo de evento que caben en el presupuesto disponible.
     */
    public function index(Request $request, int $eventId, BudgetService $budgetService): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $event = $user->events()->with('venue')->findOrFail($eventId);
        $availableBudget = $budgetService->calculateAvailableBudget($event);

        return response()->json([
            'available_budget' => $availableBudget,
            'packages' => $budgetService->suggestPackage($event),
        ]);
    }

    /**
     * Copia un paquete recomendado al evento con los proveedores seleccionados.
     */
    public function store(Request $request, int $eventId, int $packageId, BudgetService $budgetService): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $event = $user->events()->with('venue')->findOrFail($eventId);
        $package = $budgetService->suggestPackage($event)->firstWhere('id', $packageId);
        abort_unless($package instanceof Package, 404);

        $validated = $request->validate([
            'provider_ids' => ['present', 'array'],
            'provider_ids.*' => ['integer', 'distinct', 'exists:providers,id'],
        ]);

        $providerIds = array_map('intval', $validated['provider_ids']);
        $availableProviders = $package->providers->keyBy('id');
        $selectedProviders = new Collection;

        foreach ($providerIds as $providerId) {
            $provider = $availableProviders->get($providerId);

            if (! $provider instanceof Provider) {
                throw ValidationException::withMessages([
                    'provider_ids' => 'Selecciona únicamente proveedores incluidos en el paquete.',
                ]);
            }

            $selectedProviders->push($provider);
        }

        $totalPriceInCents = $selectedProviders->sum(
            fn (Provider $provider): int => (int) round((float) $provider->pivot->price * 100),
        );
        $availableBudgetInCents = (int) round($budgetService->calculateAvailableBudget($event) * 100);

        if ($totalPriceInCents > $availableBudgetInCents) {
            throw ValidationException::withMessages([
                'provider_ids' => 'El precio de los proveedores seleccionados supera el presupuesto disponible.',
            ]);
        }

        $confirmedPackage = DB::transaction(function () use ($event, $package, $selectedProviders, $totalPriceInCents): Package {
            $confirmedPackage = $event->packages()->create([
                'category' => $event->category,
                'name' => $package->name,
                'description' => $package->description,
                'estimated_price' => $totalPriceInCents / 100,
                'is_paid' => true,
            ]);
            $providerPrices = [];

            foreach ($selectedProviders as $provider) {
                $providerPrices[$provider->id] = [
                    'price' => $provider->pivot->price,
                ];
            }

            $confirmedPackage->providers()->sync($providerPrices);

            return $confirmedPackage->load('providers');
        });

        return response()->json($confirmedPackage, 201);
    }
}
