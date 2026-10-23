<?php

/**
 * @company Bigbitbyte
 * @authors Ivan Hernández Hinojosa, Jorge Darien Cabrera Bastidas, Carlos González Laines
 * @created 2026
 * @description Entrega las vistas del dashboard y detalle de eventos.
 */

namespace App\Http\Controllers;

use App\Models\Evento;
use Illuminate\Contracts\View\View;

class EventoController extends Controller
{
    /**
     * Muestra el dashboard principal de eventos.
     *
     * @return View
     */
    public function dashboard(): View
    {
        return view('eventos.index');
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
