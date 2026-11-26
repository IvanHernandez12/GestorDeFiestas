<?php

/**
 * @empresa Bigbitbyte
 *
 * @autor Ivan Hernandez Hinojosa
 *
 * @creado 25/noviembre/2026
 *
 * @modificado 25/noviembre/2026
 *
 * @descripción Centraliza los límites de eventos e invitados definidos por el plan del usuario.
 */

namespace App\Services;

use App\Models\Event;
use App\Models\User;

class PlanLimitService
{
    /**
     * Devuelve el mensaje de error cuando el usuario no puede crear otro evento.
     */
    public function eventCreationError(User $user, int $requestedGuests): ?string
    {
        $plan = $user->plan;
        $isFree = $plan?->is_free ?? true;
        $maxEvents = $plan?->max_events ?? ($isFree ? 1 : null);
        $maxGuests = $plan?->max_guests ?? ($isFree ? 50 : null);

        if ($maxEvents !== null && $user->events()->count() >= $maxEvents) {
            return $isFree
                ? 'Has alcanzado el límite de tu plan gratuito: solo puedes crear 1 evento.'
                : "Tu plan permite crear {$maxEvents} eventos.";
        }

        if ($maxGuests !== null && $requestedGuests > $maxGuests) {
            return $isFree
                ? 'Has alcanzado el límite de tu plan gratuito: máximo 50 invitados por evento.'
                : "Tu plan permite hasta {$maxGuests} invitados por evento.";
        }

        return null;
    }

    /**
     * Devuelve el mensaje de error cuando el evento ya no puede aceptar más invitados.
     */
    public function guestCreationError(User $user, Event $event): ?string
    {
        $plan = $user->plan;
        $maxGuests = $plan?->max_guests ?? ($plan?->is_free ?? true ? 50 : null);
        $permittedGuests = (int) $event->guest_count;

        if ($maxGuests !== null) {
            if ((int) $event->guest_count > $maxGuests) {
                return 'El evento supera el número de invitados permitido por tu plan.';
            }

            $permittedGuests = min($permittedGuests, $maxGuests);
        }

        if ($event->guests()->count() >= $permittedGuests) {
            return 'El evento ya alcanzó el número total de invitados registrados.';
        }

        return null;
    }
}
