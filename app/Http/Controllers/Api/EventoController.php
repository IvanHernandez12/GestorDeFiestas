<?php

/**
 * @company Bigbitbyte
 * @authors Ivan Hernández Hinojosa, Jorge Darien Cabrera Bastidas, Carlos González Laines
 * @created 2026
 * @modified 27/octubre/2026
 * @description Gestiona las operaciones REST de eventos.
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Evento;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

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
        if (!$request->filled('folio')) {
            $request->merge([
                'folio' => 'EVT-' . str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT),
            ]);
        }

        if (!$request->filled('estado')) {
            $request->merge(['estado' => 'Planificando']);
        }

        if (!$request->filled('prioridad_invitados')) {
            $request->merge(['prioridad_invitados' => ['Familia', 'Amigos', 'Trabajo', 'General']]);
        }

        $datos = $request->validate([
            'folio' => ['required', 'string', 'max:20', 'unique:eventos,folio'],
            'nombre' => ['required', 'string', 'max:60', 'not_regex:/\p{N}/u'],
            'tipo' => ['required', Rule::in(['Boda', 'XV Años', 'Cumpleaños', 'Graduación', 'Bautizo', 'Corporativo', 'Reunión Social'])],
            'estado' => ['required', 'string', 'max:40'],
            'fecha_evento' => ['required', 'date', 'after_or_equal:' . now()->subYears(2)->toDateString(), 'before_or_equal:' . now()->addYears(2)->toDateString()],
            'ubicacion' => ['required', 'string', 'max:80'],
            'numero_invitados' => ['required', 'integer', 'min:1', 'max:5000'],
            'presupuesto' => ['required', 'numeric', 'min:500', 'max:10000000', 'decimal:0,2'],
            'reglamento' => ['nullable', 'string', 'max:250'],
            'prioridad_invitados' => ['nullable', 'array'],
        ]);

        $evento = Evento::query()->create([
            'folio' => $datos['folio'],
            'nombre' => $datos['nombre'],
            'tipo' => $datos['tipo'],
            'estado' => $datos['estado'],
            'fecha_evento' => $datos['fecha_evento'],
            'ubicacion' => $datos['ubicacion'],
            'numero_invitados' => $datos['numero_invitados'] ?? 0,
            'presupuesto' => $datos['presupuesto'] ?? 0,
            'reglamento' => $datos['reglamento'] ?? null,
            'prioridad_invitados' => $datos['prioridad_invitados'] ?? ['Familia', 'Amigos', 'Trabajo', 'General'],
        ]);

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
            'nombre' => ['sometimes', 'string', 'max:60', 'not_regex:/\p{N}/u'],
            'tipo' => ['sometimes', Rule::in(['Boda', 'XV Años', 'Cumpleaños', 'Graduación', 'Bautizo', 'Corporativo', 'Reunión Social'])],
            'estado' => ['sometimes', 'string', 'max:40'],
            'fecha_evento' => ['sometimes', 'date'],
            'ubicacion' => ['sometimes', 'string', 'max:80'],
            'numero_invitados' => ['sometimes', 'integer', 'min:1', 'max:5000'],
            'presupuesto' => ['sometimes', 'numeric', 'min:500', 'max:10000000', 'decimal:0,2'],
            'reglamento' => ['nullable', 'string', 'max:250'],
            'prioridad_invitados' => ['nullable', 'array'],
            'distribucion_salon' => ['nullable', 'array'],
            'proveedores_seleccionados' => ['nullable', 'array', 'max:8'],
            'proveedores_seleccionados.*.key' => ['required', 'string', 'max:40'],
            'proveedores_seleccionados.*.name' => ['required', 'string', 'max:100'],
            'proveedores_seleccionados.*.category' => ['required', 'string', 'max:60'],
            'proveedores_seleccionados.*.cost' => ['required', 'numeric', 'min:1', 'max:10000000'],
            'proveedores_seleccionados.*.delivery_status' => ['sometimes', Rule::in(['Pendiente', 'En proceso', 'Entregado'])],
            'proveedores_seleccionados.*.delivery_date' => ['nullable', 'date'],
            'proveedores_seleccionados.*.phone' => ['nullable', 'string', 'max:30'],
            'proveedores_seleccionados.*.email' => ['nullable', 'email', 'max:160'],
        ]);

        if (array_key_exists('proveedores_seleccionados', $datos)) {
            $proveedores = collect($datos['proveedores_seleccionados'] ?? []);
            $costoTotal = $proveedores->sum('cost');
            $limiteTotal = (float) $evento->presupuesto * 0.70;
            $limiteIndividual = (float) $evento->presupuesto * 0.35;
            $costoExcesivo = $proveedores->contains(
                fn (array $proveedor): bool => (float) $proveedor['cost'] > $limiteIndividual
            );

            if ($costoExcesivo || $costoTotal > $limiteTotal) {
                throw ValidationException::withMessages([
                    'proveedores_seleccionados' => 'La selección debe respetar el límite de 35 % por proveedor y 70 % en conjunto.',
                ]);
            }
        }

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
