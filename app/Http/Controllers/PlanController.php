<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PlanController extends Controller
{
    /**
     * Muestra los planes disponibles y sus características.
     */
    public function index(Request $request): Response
    {
        $featuresBySlug = [
            'free' => ['1 evento activo', 'Hasta 50 invitados', 'Planeación y distribución de mesas'],
            'basic' => ['Hasta 5 eventos', 'Hasta 200 invitados por evento', 'Planeación y distribución de mesas'],
            'premium' => ['Eventos ilimitados', 'Hasta 1000 invitados por evento', 'Planeación y distribución de mesas'],
        ];

        return Inertia::render('Plans/Index', [
            'plans' => Plan::query()
                ->whereIn('slug', array_keys($featuresBySlug))
                ->orderBy('price')
                ->get()
                ->map(fn (Plan $plan): array => [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'slug' => $plan->slug,
                    'price' => (float) $plan->price,
                    'is_free' => $plan->is_free,
                    'features' => $featuresBySlug[$plan->slug],
                ]),
            'currentPlanId' => $request->user()?->plan_id,
        ]);
    }

    /**
     * Inicia la suscripción simulada para un plan de pago.
     */
    public function subscribe(Request $request, Plan $plan): RedirectResponse
    {
        abort_unless($request->user() instanceof User, 401);

        if ($plan->is_free) {
            return back()->withErrors(['subscribe' => 'El plan Gratuito no requiere suscripción.']);
        }

        if (! config('services.payments.simulation')) {
            return back()->withErrors(['subscribe' => 'La suscripción de planes requiere PAYMENT_SIMULATION=true.']);
        }

        return to_route('payments.simulate', [
            'type' => 'plan',
            'id' => $plan->id,
        ]);
    }
}
