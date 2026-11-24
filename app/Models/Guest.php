<?php

/**
 * @empresa Bigbitbyte
 *
 * @autor Ivan Hernandez Hinojosa
 *
 * @creado 20/noviembre/2026
 *
 * @modificado 24/noviembre/2026
 *
 * @descripción Representa a un invitado y su código QR individual.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Guest extends Model
{
    /** @var list<string> */
    protected $fillable = ['event_id', 'name', 'email', 'category_id', 'qr_token'];

    /** @return BelongsTo<Event, $this> */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /** @return BelongsTo<GuestCategory, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(GuestCategory::class, 'category_id');
    }
}
