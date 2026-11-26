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
use App\Services\PlanLimitService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPlanLimits
{
    /**
     * Crea el middleware con el servicio de límites inyectado.
     */
    public function __construct(private PlanLimitService $planLimitService) {}

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

        $error = $this->planLimitService->eventCreationError($user, $request->integer('guest_count'));

        if ($error !== null) {
            return response()->json(['message' => $error], 403);
        }

        return $next($request);
    }
}
