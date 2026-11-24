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
 * @descripción Bloquea la creación de eventos que excedan el plan gratuito.
 */

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPlanLimits
{
    /**
     * Detiene solicitudes que excedan los límites configurados del plan gratuito.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return response()->json(['message' => 'No autenticado.'], 401);
        }

        $plan = $user->plan;

        if ($plan !== null && ! $plan->is_free) {
            return $next($request);
        }

        $maximumEvents = $plan?->max_events ?? 1;
        $maximumGuests = $plan?->max_guests ?? 50;

        if ($user->events()->count() >= $maximumEvents) {
            return response()->json([
                'message' => 'Has alcanzado el límite de tu plan gratuito: solo puedes crear 1 evento.',
            ], 403);
        }

        if ($request->integer('guest_count') > $maximumGuests) {
            return response()->json([
                'message' => 'Has alcanzado el límite de tu plan gratuito: máximo 50 invitados por evento.',
            ], 403);
        }

        return $next($request);
    }
}
