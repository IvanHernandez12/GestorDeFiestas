<?php

/**
 * @company Bigbitbyte
 * @authors Ivan Hernández Hinojosa
 * @created 20/octubre/2026
 * @modified 27/octubre/2026
 * @description Entrega las vistas del dashboard y detalle de eventos.
 */

namespace App\Http\Controllers;

use App\Models\Evento;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
            'nombre' => ['required', 'string', 'max:60', 'not_regex:/\p{N}/u'],
            'tipo' => ['required', Rule::in(['Boda', 'XV Años', 'Cumpleaños', 'Graduación', 'Bautizo', 'Corporativo', 'Reunión Social'])],
            'estado' => ['required', 'string', 'max:40'],
            'fecha_evento' => ['required', 'date', 'after_or_equal:' . now()->subYears(2)->toDateString(), 'before_or_equal:' . now()->addYears(2)->toDateString()],
            'ubicacion' => ['required', 'string', 'max:80'],
            'numero_invitados' => ['required', 'integer', 'min:1', 'max:5000'],
            'presupuesto' => ['required', 'numeric', 'min:500', 'max:10000000', 'decimal:0,2'],
            'reglamento' => ['nullable', 'string', 'max:250'],
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

        return redirect()->route('eventos.proveedores', $evento)->with('status', 'Evento creado. Elige proveedores dentro de tu presupuesto.');
    }

    /**
     * Muestra las propuestas de proveedores para el presupuesto del evento.
     *
     * @param Evento $evento
     * @return View
     */
    public function proveedores(Evento $evento): View
    {
        return view('eventos.proveedores', ['evento' => $evento]);
    }

    /**
     * Muestra el detalle de un evento.
     *
     * @param Evento $evento
     * @return View
     */
    public function detalle(Evento $evento): View
    {
        return view('eventos.show', [
            'eventoId' => $evento->id,
            'evento' => $evento,
        ]);
    }

    /**
     * Actualiza la información de un evento desde formulario web o modal.
     *
     * @param Request $request
     * @param Evento $evento
     * @return RedirectResponse
     */
    public function update(Request $request, Evento $evento): RedirectResponse
    {
        $datos = $request->validate([
            'nombre' => ['sometimes', 'required', 'string', 'max:60', 'not_regex:/\p{N}/u'],
            'tipo' => ['sometimes', 'required', Rule::in(['Boda', 'XV Años', 'Cumpleaños', 'Graduación', 'Bautizo', 'Corporativo', 'Reunión Social'])],
            'estado' => ['sometimes', 'required', 'string', 'max:40'],
            'fecha_evento' => ['sometimes', 'required', 'date'],
            'ubicacion' => ['sometimes', 'required', 'string', 'max:80'],
            'numero_invitados' => ['sometimes', 'integer', 'min:1', 'max:5000'],
            'presupuesto' => ['sometimes', 'numeric', 'min:500', 'max:10000000', 'decimal:0,2'],
            'reglamento' => ['nullable', 'string', 'max:250'],
        ]);

        $evento->update($datos);

        return redirect()->route('eventos.detalle', $evento)->with('status', '¡Información del evento "' . $evento->nombre . '" actualizada correctamente!');
    }

    /**
     * Elimina un evento y redirige al dashboard.
     *
     * @param Evento $evento
     * @return RedirectResponse
     */
    public function destroy(Evento $evento): RedirectResponse
    {
        $nombre = $evento->nombre;
        $evento->delete();

        return redirect()->route('eventos.dashboard')->with('status', 'El evento "' . $nombre . '" ha sido eliminado correctamente.');
    }
}
