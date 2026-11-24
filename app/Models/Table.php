<?php

/**
 * @empresa Bigbitbyte
 *
 * @autor Ivan Hernandez Hinojosa
 *
 * @creado 13/noviembre/2026
 *
 * @modificado 24/noviembre/2026
 *
 * @descripción Representa una mesa del plano de un evento.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Table extends Model
{
    /** @var list<string> */
    protected $fillable = ['event_id', 'guest_category_id', 'table_number', 'capacity', 'position_x', 'position_y'];

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

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'table_number' => 'integer',
            'capacity' => 'integer',
            'position_x' => 'decimal:2',
            'position_y' => 'decimal:2',
        ];
    }
}
