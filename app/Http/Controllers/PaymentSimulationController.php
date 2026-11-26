<?php

namespace App\Http\Controllers;

use App\Models\Package;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PaymentSimulationController extends Controller
{
    /**
     * Presenta el pago de prueba para un paquete o plan.
     */
    public function show(Request $request): Response
    {
        abort_unless(config('services.payments.simulation'), 404);

        $validated = $request->validate([
            'type' => ['required', Rule::in(['package', 'plan'])],
            'id' => ['required', 'integer', 'min:1'],
        ]);
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $intent = [
            'type' => $validated['type'],
            'id' => $validated['id'],
            'token' => (string) Str::uuid(),
        ];
        $request->session()->put('payment_simulation_intent', $intent);

        if ($validated['type'] === 'package') {
            $purchase = Package::query()
                ->whereKey($validated['id'])
                ->whereHas('event', fn ($query) => $query->where('user_id', $user->id))
                ->firstOrFail();

            return Inertia::render('Payments/SimulatePayment', [
                'type' => 'package',
                'id' => $purchase->id,
                'token' => $intent['token'],
                'title' => $purchase->name,
                'referenceAmount' => (float) $purchase->estimated_price,
                'chargeAmount' => 1.00,
            ]);
        }

        $plan = Plan::query()->whereKey($validated['id'])->where('is_free', false)->firstOrFail();

        return Inertia::render('Payments/SimulatePayment', [
            'type' => 'plan',
            'id' => $plan->id,
            'token' => $intent['token'],
            'title' => 'Plan '.$plan->name,
            'referenceAmount' => (float) $plan->price,
            'chargeAmount' => 1.00,
        ]);
    }

    /**
     * Confirma la compra de prueba y actualiza el recurso correspondiente.
     */
    public function confirm(Request $request): RedirectResponse
    {
        abort_unless(config('services.payments.simulation'), 404);

        $validated = $request->validate([
            'type' => ['required', Rule::in(['package', 'plan'])],
            'id' => ['required', 'integer', 'min:1'],
            'token' => ['required', 'string'],
        ]);
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $intent = $request->session()->get('payment_simulation_intent');
        $matchesIntent = is_array($intent)
            && $intent['type'] === $validated['type']
            && (int) ($intent['id'] ?? 0) === (int) $validated['id']
            && hash_equals((string) ($intent['token'] ?? ''), $validated['token']);

        if (! $matchesIntent) {
            throw ValidationException::withMessages([
                'token' => 'Inicia el pago de prueba desde la página de confirmación.',
            ]);
        }

        $request->session()->forget('payment_simulation_intent');

        DB::transaction(function () use ($validated, $user): void {
            if ($validated['type'] === 'package') {
                $purchase = Package::query()
                    ->whereKey($validated['id'])
                    ->whereHas('event', fn ($query) => $query->where('user_id', $user->id))
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($purchase->is_paid) {
                    throw ValidationException::withMessages([
                        'id' => 'Este paquete ya fue pagado.',
                    ]);
                }

                $purchase->update(['is_paid' => true]);

                return;
            }

            $plan = Plan::query()->whereKey($validated['id'])->where('is_free', false)->firstOrFail();
            $lockedUser = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $currentPlan = $lockedUser->plan;

            if ($currentPlan !== null
                && ! $currentPlan->is_free
                && (float) $currentPlan->price >= (float) $plan->price) {
                throw ValidationException::withMessages([
                    'id' => 'No es posible cambiar a un plan de menor o igual precio.',
                ]);
            }

            $lockedUser->update(['plan_id' => $plan->id]);
        });

        return to_route('web.events.index')->with('status', 'Pago de prueba confirmado.');
    }
}
