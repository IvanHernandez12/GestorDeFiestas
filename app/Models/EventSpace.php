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
 * @descripción Representa un espacio personalizable del plano de un evento.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventSpace extends Model
{
    /** @var list<string> */
    protected $fillable = ['event_id', 'name', 'type', 'position_x', 'position_y', 'width', 'height'];

    /** @return BelongsTo<Event, $this> */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'position_x' => 'float',
            'position_y' => 'float',
            'width' => 'integer',
            'height' => 'integer',
        ];
    }
}
