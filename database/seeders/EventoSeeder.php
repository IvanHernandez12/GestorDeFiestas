<?php

/**
 * @company Bigbitbyte
 * @authors Ivan Hernández Hinojosa, Jorge Darien Cabrera Bastidas, Carlos González Laines
 * @created 2026
 * @description Registra el evento de referencia para la demostración inicial.
 */

namespace Database\Seeders;

use App\Models\Evento;
use Illuminate\Database\Seeder;

class EventoSeeder extends Seeder
{
    /**
     * Ejecuta los datos de demostración de eventos.
     *
     * @return void
     */
    public function run(): void
    {
        Evento::query()->firstOrCreate(
            ['folio' => 'EVT-045476'],
            [
                'nombre' => 'boda de laura',
                'tipo' => 'Boda',
                'estado' => 'Planificando',
                'fecha_evento' => '2026-10-30',
                'ubicacion' => 'toluca',
                'numero_invitados' => 30,
                'presupuesto' => 100000,
                'reglamento' => 'No niños',
                'prioridad_invitados' => ['Familia', 'Amigos', 'Trabajo', 'General'],
            ],
        );
    }
}
