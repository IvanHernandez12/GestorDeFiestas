<?php

/**
 * @empresa Bigbitbyte
 *
 * @autor Ivan Hernandez Hinojosa
 *
 * @creado 13/noviembre/2026
 *
 * @modificado 26/noviembre/2026
 *
 * @descripción Representa una categoría priorizada de invitados con invitación grupal.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GuestCategory extends Model
{
    /** @var list<string> */
    protected $fillable = ['event_id', 'name', 'guest_count', 'priority', 'public_uuid'];

    /** @return BelongsTo<Event, $this> */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /** @return HasMany<Table, $this> */
    public function tables(): HasMany
    {
        return $this->hasMany(Table::class);
    }

    /** @return HasMany<Guest, $this> */
    public function guests(): HasMany
    {
        return $this->hasMany(Guest::class, 'category_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'guest_count' => 'integer',
            'priority' => 'integer',
        ];
    }
}
