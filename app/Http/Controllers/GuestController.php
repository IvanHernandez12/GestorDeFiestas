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
 * @descripción Lista y registra invitados en eventos del usuario autenticado.
 */

namespace App\Http\Controllers;

use App\Models\Guest;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class GuestController extends Controller
{
    /**
     * Lista los invitados de un evento que pertenece al usuario autenticado.
     *
     * @return JsonResponse<array{guests: Collection<int, Guest>}>
     */
    public function index(Request $request, int $eventId): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $event = $user->events()->findOrFail($eventId);
        $guests = $event->guests()
            ->with('category')
            ->orderBy('name')
            ->get();

        return response()->json(['guests' => $guests]);
    }

    /**
     * Crea un invitado con un token QR único para un evento propio.
     *
     * @return JsonResponse<array{guest: Guest}>
     */
    public function store(Request $request, int $eventId): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $event = $user->events()->findOrFail($eventId);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'category_id' => [
                'required',
                'integer',
                Rule::exists('guest_categories', 'id')->where('event_id', $event->id),
            ],
        ]);

        $guest = $event->guests()->create([
            ...$validated,
            'qr_token' => (string) Str::uuid(),
        ]);

        return response()->json([
            'guest' => $guest->load('category'),
        ], 201);
    }
}
