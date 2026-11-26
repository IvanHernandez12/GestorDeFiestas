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
 * @descripción Administra los espacios personalizables del plano de un evento propio.
 */

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventSpaceController extends Controller
{
    /**
     * Crea un espacio en el plano de un evento propio.
     */
    public function store(Request $request, int $eventId): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $event = $user->events()->findOrFail($eventId);
        $validated = $request->validate($this->rules(true));

        $space = $event->eventSpaces()->create([
            'name' => $validated['name'],
            'type' => $validated['type'],
            'width' => $validated['width'] ?? 160,
            'height' => $validated['height'] ?? 100,
            'position_x' => $validated['position_x'] ?? 0,
            'position_y' => $validated['position_y'] ?? 0,
        ]);

        return response()->json($space, 201);
    }

    /**
     * Actualiza un espacio del plano de un evento propio.
     */
    public function update(Request $request, int $eventId, int $spaceId): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $event = $user->events()->findOrFail($eventId);
        $validated = $request->validate($this->rules(false));
        $space = $event->eventSpaces()->findOrFail($spaceId);
        $space->update($validated);

        return response()->json($space);
    }

    /**
     * Elimina un espacio del plano de un evento propio.
     */
    public function destroy(Request $request, int $eventId, int $spaceId): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $event = $user->events()->findOrFail($eventId);
        $space = $event->eventSpaces()->findOrFail($spaceId);
        $space->delete();

        return response()->json(['id' => $spaceId]);
    }

    /**
     * @return array<string, list<string>>
     */
    private function rules(bool $creating): array
    {
        return [
            'name' => [$creating ? 'required' : 'sometimes', 'string', 'max:100'],
            'type' => [$creating ? 'required' : 'sometimes', 'string', 'in:bar,dance_floor,gift_table,stage,catering,photo_zone,other'],
            'width' => ['sometimes', 'integer', 'min:60', 'max:600'],
            'height' => ['sometimes', 'integer', 'min:60', 'max:600'],
            'position_x' => ['sometimes', 'numeric', 'min:0', 'max:1000'],
            'position_y' => ['sometimes', 'numeric', 'min:0', 'max:700'],
        ];
    }
}
