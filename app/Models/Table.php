<?php

/**
 * @empresa Bigbitbyte
 *
 * @autor Ivan Hernandez Hinojosa
 *
 * @creado 13/noviembre/2026
 *
 * @modificado 25/noviembre/2026
 *
 * @descripción Representa una mesa del plano de un evento y sus invitados sentados.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Table extends Model
{
    /** @var list<string> */
    protected $fillable = ['event_id', 'guest_category_id', 'table_number', 'capacity', 'shape', 'width', 'height', 'position_x', 'position_y'];

    /** @return BelongsTo<Event, $this> */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /** @return BelongsTo<GuestCategory, $this> */
    public function guestCategory(): BelongsTo
    {
        return $this->belongsTo(GuestCategory::class);
    }

    /** @return HasMany<Guest, $this> */
    public function guests(): HasMany
    {
        return $this->hasMany(Guest::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'table_number' => 'integer',
            'capacity' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'position_x' => 'decimal:2',
            'position_y' => 'decimal:2',
        ];
    }
}
