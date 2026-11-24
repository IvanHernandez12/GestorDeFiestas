<?php

/**
 * @empresa Bigbitbyte
 *
 * @autor Ivan Hernandez Hinojosa
 *
 * @creado 13/noviembre/2026
 *
 * @modificado 24/noviembre/2026
 *
 * @descripción Lista, crea y muestra eventos del usuario autenticado.
 */

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\User;
use App\Services\TableDistributionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Validator as ValidationValidator;

class EventController extends Controller
{
    /**
     * Lista los eventos del usuario autenticado.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return response()->json(['message' => 'No autenticado.'], 401);
        }

        $events = $user->events()
            ->with('venue')
            ->latest()
            ->get();

        return response()->json($events);
    }

    /**
     * Crea un evento y genera su UUID público.
     */
    public function store(Request $request, TableDistributionService $tableDistributionService): JsonResponse
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return response()->json(['message' => 'No autenticado.'], 401);
        }

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:100'],
            'event_date' => ['required', 'date', 'after_or_equal:'.now()->addDays(15)->toDateString()],
            'guest_count' => ['required', 'integer', 'min:1', 'max:100000'],
            'total_budget' => ['required', 'numeric', 'min:5000', 'max:100000000'],
            'venue_id' => ['required', 'integer', 'exists:venues,id'],
            'rules' => ['nullable', 'string', 'max:5000'],
            'guest_categories' => ['sometimes', 'array', 'min:1', 'max:255'],
            'guest_categories.*.name' => ['required', 'string', 'max:100'],
            'guest_categories.*.guest_count' => ['required', 'integer', 'min:0', 'max:100000'],
            'guest_categories.*.priority' => ['sometimes', 'integer', 'min:1', 'max:255', 'distinct'],
        ], [
            'event_date.after_or_equal' => 'La fecha debe ser al menos 15 días después de hoy',
        ]);

        $validator->after(function (ValidationValidator $validator) use ($request): void {
            if ($validator->errors()->isNotEmpty() || ! $request->exists('guest_categories')) {
                return;
            }

            $assignedGuestCount = array_sum(array_column($request->input('guest_categories'), 'guest_count'));

            if ($assignedGuestCount > (int) $request->input('guest_count')) {
                $validator->errors()->add('guest_categories', 'La suma de invitados por categoría no puede exceder el total del evento.');
            }
        });

        $validated = $validator->validate();
        $guestCategories = $validated['guest_categories'] ?? [];
        unset($validated['guest_categories']);

        $event = DB::transaction(function () use ($user, $validated, $guestCategories, $tableDistributionService): Event {
            $event = $user->events()->create([
                ...$validated,
                'public_uuid' => (string) Str::uuid(),
            ]);

            foreach ($guestCategories as $index => $guestCategory) {
                $event->guestCategories()->create([
                    'name' => $guestCategory['name'],
                    'guest_count' => $guestCategory['guest_count'],
                    'priority' => $guestCategory['priority'] ?? $index + 1,
                ]);
            }

            $tableDistributionService->distribute($event);

            return $event;
        });

        return response()->json($event->load(['venue', 'guestCategories', 'tables.guestCategory']), 201);
    }

    /**
     * Muestra un evento solo si pertenece al usuario autenticado.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return response()->json(['message' => 'No autenticado.'], 401);
        }

        $event = $user->events()
            ->with(['venue', 'guestCategories', 'tables', 'packages.providers'])
            ->findOrFail($id);

        return response()->json($event);
    }
}
