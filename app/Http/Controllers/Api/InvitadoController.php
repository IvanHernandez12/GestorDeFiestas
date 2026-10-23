<?php

/**
 * @company Bigbitbyte
 * @authors Ivan Hernández Hinojosa, Jorge Darien Cabrera Bastidas, Carlos González Laines
 * @created 2026
 * @description Gestiona las operaciones REST de invitados de eventos.
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Evento;
use App\Models\Invitado;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InvitadoController extends Controller
{
    /**
     * Lista los invitados asociados a un evento.
     *
     * @param Evento $evento
     * @return JsonResponse
     */
    public function index(Evento $evento): JsonResponse
    {
        return response()->json($evento->invitados()->orderBy('nombre')->get());
    }

    /**
     * Muestra un invitado que pertenece al evento indicado.
     *
     * @param Evento $evento
     * @param Invitado $invitado
     * @return JsonResponse
     */
    public function show(Evento $evento, Invitado $invitado): JsonResponse
    {
        abort_unless($invitado->evento_id === $evento->id, 404);

        return response()->json($invitado);
    }

    /**
     * Agrega un invitado al evento indicado.
     *
     * @param Request $request
     * @param Evento $evento
     * @return JsonResponse
     */
    public function store(Request $request, Evento $evento): JsonResponse
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:120'],
            'correo' => ['required', 'email', 'max:160'],
            'categoria' => ['required', Rule::in(['Familia', 'Amigos', 'Trabajo', 'General'])],
        ]);

        $invitado = $evento->invitados()->create($datos);

        return response()->json($invitado, 201);
    }

    /**
     * Actualiza los datos de un invitado del evento.
     *
     * @param Request $request
     * @param Evento $evento
     * @param Invitado $invitado
     * @return JsonResponse
     */
    public function update(Request $request, Evento $evento, Invitado $invitado): JsonResponse
    {
        abort_unless($invitado->evento_id === $evento->id, 404);

        $datos = $request->validate([
            'nombre' => ['sometimes', 'string', 'max:120'],
            'correo' => ['sometimes', 'email', 'max:160'],
            'categoria' => ['sometimes', Rule::in(['Familia', 'Amigos', 'Trabajo', 'General'])],
        ]);

        $invitado->update($datos);

        return response()->json($invitado->fresh());
    }

    /**
     * Elimina un invitado del evento.
     *
     * @param Evento $evento
     * @param Invitado $invitado
     * @return JsonResponse
     */
    public function destroy(Evento $evento, Invitado $invitado): JsonResponse
    {
        abort_unless($invitado->evento_id === $evento->id, 404);
        $invitado->delete();

        return response()->json(null, 204);
    }
}
