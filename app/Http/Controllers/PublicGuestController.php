<?php

/**
 * @empresa Bigbitbyte
 *
 * @autor Ivan Hernandez Hinojosa
 *
 * @creado 20/noviembre/2026
 *
 * @modificado 24/noviembre/2026
 *
 * @descripción Expone los datos de invitación a partir de un token QR público.
 */

namespace App\Http\Controllers;

use App\Models\Guest;
use App\Models\Venue;
use Illuminate\Http\JsonResponse;

class PublicGuestController extends Controller
{
    /**
     * Devuelve los datos del invitado, su evento y el salón mediante su token QR.
     */
    public function show(string $qrToken): JsonResponse
    {
        $guest = Guest::query()
            ->with(['category', 'event.venue'])
            ->where('qr_token', $qrToken)
            ->first();

        if (! $guest instanceof Guest) {
            return response()->json([
                'message' => 'Invitación no encontrada',
            ], 404);
        }

        $event = $guest->event;
        $venue = $event->venue;

        return response()->json([
            'guest' => [
                'name' => $guest->name,
                'category' => $guest->category->name,
            ],
            'event' => [
                'name' => $event->name,
                'date' => $event->event_date?->toDateString(),
                'rules' => $event->rules,
            ],
            'venue' => $venue instanceof Venue ? [
                'name' => $venue->name,
                'address' => $venue->address,
                'lat' => $venue->lat === null ? null : (float) $venue->lat,
                'lng' => $venue->lng === null ? null : (float) $venue->lng,
                'google_maps_url' => $venue->google_maps_url,
            ] : null,
        ]);
    }
}
