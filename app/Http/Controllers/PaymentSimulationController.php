<?php

namespace App\Http\Controllers;

use App\Models\Package;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
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

        if ($validated['type'] === 'package') {
            $purchase = Package::query()
                ->whereKey($validated['id'])
                ->whereHas('event', fn ($query) => $query->where('user_id', $user->id))
                ->firstOrFail();

            return Inertia::render('Payments/SimulatePayment', [
                'type' => 'package',
                'id' => $purchase->id,
                'title' => $purchase->name,
                'referenceAmount' => (float) $purchase->estimated_price,
                'chargeAmount' => 1.00,
            ]);
        }

        $plan = Plan::query()->whereKey($validated['id'])->where('is_free', false)->firstOrFail();

        return Inertia::render('Payments/SimulatePayment', [
            'type' => 'plan',
            'id' => $plan->id,
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
        ]);
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        DB::transaction(function () use ($validated, $user): void {
            if ($validated['type'] === 'package') {
                $purchase = Package::query()
                    ->whereKey($validated['id'])
                    ->whereHas('event', fn ($query) => $query->where('user_id', $user->id))
                    ->lockForUpdate()
                    ->firstOrFail();
                $purchase->update(['is_paid' => true]);

                return;
            }

            $plan = Plan::query()->whereKey($validated['id'])->where('is_free', false)->firstOrFail();
            $lockedUser = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $lockedUser->update(['plan_id' => $plan->id]);
        });

        return to_route('web.events.index')->with('status', 'Pago de prueba confirmado.');
    }
}
