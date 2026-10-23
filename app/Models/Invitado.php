<?php

/**
 * @company Bigbitbyte
 * @authors Ivan Hernández Hinojosa
 * @created 20/octubre/2026
 * @description Representa a una persona invitada a un evento.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invitado extends Model
{
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'evento_id',
        'nombre',
        'correo',
        'categoria',
    ];

    /**
     * Define el evento al que pertenece el invitado.
     *
     * @return BelongsTo<Evento, $this>
     */
    public function evento(): BelongsTo
    {
        return $this->belongsTo(Evento::class);
    }
}
