<?php

/**
 * @company Bigbitbyte
 * @authors Ivan Hernández Hinojosa, Jorge Darien Cabrera Bastidas, Carlos González Laines
 * @created 2026
 * @description Gestiona las operaciones REST de eventos.
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Evento;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EventoController extends Controller
{
    /**
     * Lista eventos paginados con el total de invitados registrados.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $eventos = Evento::query()
            ->withCount('invitados')
            ->orderBy('fecha_evento')
            ->paginate($request->integer('per_page', 12));

        return response()->json($eventos);
    }

    /**
     * Muestra un evento junto con sus invitados.
     *
     * @param Evento $evento
     * @return JsonResponse
     */
    public function show(Evento $evento): JsonResponse
    {
        return response()->json($evento->load('invitados'));
    }

    /**
     * Crea un evento a partir de datos validados.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'folio' => ['required', 'string', 'max:20', 'unique:eventos,folio'],
            'nombre' => ['required', 'string', 'max:120'],
            'tipo' => ['required', 'string', 'max:40'],
            'estado' => ['required', 'string', 'max:40'],
            'fecha_evento' => ['required', 'date'],
            'ubicacion' => ['required', 'string', 'max:160'],
            'numero_invitados' => ['sometimes', 'integer', 'min:0'],
            'presupuesto' => ['sometimes', 'numeric', 'min:0'],
            'reglamento' => ['nullable', 'string', 'max:500'],
            'prioridad_invitados' => ['nullable', 'array'],
        ]);

        $evento = Evento::query()->create($datos);

        return response()->json($evento, 201);
    }

    /**
     * Actualiza los datos editables de un evento.
     *
     * @param Request $request
     * @param Evento $evento
     * @return JsonResponse
     */
    public function update(Request $request, Evento $evento): JsonResponse
    {
        $datos = $request->validate([
            'folio' => ['sometimes', 'string', 'max:20', Rule::unique('eventos', 'folio')->ignore($evento->id)],
            'nombre' => ['sometimes', 'string', 'max:120'],
            'tipo' => ['sometimes', 'string', 'max:40'],
            'estado' => ['sometimes', 'string', 'max:40'],
            'fecha_evento' => ['sometimes', 'date'],
            'ubicacion' => ['sometimes', 'string', 'max:160'],
            'numero_invitados' => ['sometimes', 'integer', 'min:0'],
            'presupuesto' => ['sometimes', 'numeric', 'min:0'],
            'reglamento' => ['nullable', 'string', 'max:500'],
            'prioridad_invitados' => ['nullable', 'array'],
        ]);

        $evento->update($datos);

        return response()->json($evento->fresh());
    }

    /**
     * Elimina un evento y sus invitados asociados.
     *
     * @param Evento $evento
     * @return JsonResponse
     */
    public function destroy(Evento $evento): JsonResponse
    {
        $evento->delete();

        return response()->json(null, 204);
    }
}
