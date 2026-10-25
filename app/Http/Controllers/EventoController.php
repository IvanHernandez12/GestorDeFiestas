<?php

/**
 * @company Bigbitbyte
 * @authors Ivan Hernández Hinojosa.
 * @created 20/octubre/2026
 * @description Entrega las vistas del dashboard y detalle de eventos.
 */

namespace App\Http\Controllers;

use App\Models\Evento;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EventoController extends Controller
{
    /**
     * Muestra el dashboard principal de eventos.
     *
     * @return View
     */
    public function dashboard(): View
    {
        $eventos = Evento::query()
            ->withCount('invitados')
            ->orderBy('fecha_evento')
            ->get();

        return view('eventos.index', compact('eventos'));
    }

    /**
     * Muestra el formulario para registrar un evento.
     *
     * @return View
     */
    public function crear(): View
    {
        return view('eventos.create');
    }

    /**
     * Almacena un nuevo evento desde petición web estándar.
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function store(Request $request): RedirectResponse
    {
        if (!$request->filled('folio')) {
            $request->merge([
                'folio' => 'EVT-' . str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT),
            ]);
        }

        if (!$request->filled('estado')) {
            $request->merge(['estado' => 'Planificando']);
        }

        $datos = $request->validate([
            'folio' => ['required', 'string', 'max:20', 'unique:eventos,folio'],
            'nombre' => ['required', 'string', 'max:120'],
            'tipo' => ['required', 'string', 'max:40'],
            'estado' => ['required', 'string', 'max:40'],
            'fecha_evento' => ['required', 'date'],
            'ubicacion' => ['required', 'string', 'max:160'],
            'numero_invitados' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'presupuesto' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'reglamento' => ['nullable', 'string', 'max:500'],
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
            'prioridad_invitados' => ['Familia', 'Amigos', 'Trabajo', 'General'],
        ]);

        return redirect()->route('eventos.dashboard')->with('status', '¡Evento "' . $evento->nombre . '" iniciado exitosamente!');
    }

    /**
     * Muestra el detalle de un evento.
     *
     * @param Evento $evento
     * @return View
     */
    public function detalle(Evento $evento): View
    {
        return view('eventos.show', ['eventoId' => $evento->id]);
    }
}
