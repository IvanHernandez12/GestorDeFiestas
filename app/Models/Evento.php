<?php

/**
 * @company Bigbitbyte
 * @authors Ivan Hernández Hinojosa
 * @created 20/octubre/2026
 * @description Representa un evento y sus invitados asociados.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Evento extends Model
{
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'folio',
        'nombre',
        'tipo',
        'estado',
        'fecha_evento',
        'ubicacion',
        'numero_invitados',
        'presupuesto',
        'reglamento',
        'prioridad_invitados',
        'distribucion_salon',
    ];

    /**
     * Define los invitados pertenecientes al evento.
     *
     * @return HasMany<Invitado, $this>
     */
    public function invitados(): HasMany
    {
        return $this->hasMany(Invitado::class);
    }

    /**
     * Obtiene las conversiones de atributos del modelo.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha_evento' => 'date',
            'numero_invitados' => 'integer',
            'presupuesto' => 'decimal:2',
            'prioridad_invitados' => 'array',
            'distribucion_salon' => 'array',
        ];
    }
}
