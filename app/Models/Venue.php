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
 * @descripción Representa un salón con capacidad y estimación de precio.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Venue extends Model
{
    public $timestamps = false;

    /** @var list<string> */
    protected $fillable = ['name', 'address', 'lat', 'lng', 'capacity', 'min_price', 'max_price', 'contact_info', 'google_maps_url'];

    /** @return HasMany<Event, $this> */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'lat' => 'decimal:7',
            'lng' => 'decimal:7',
            'capacity' => 'integer',
            'min_price' => 'decimal:2',
            'max_price' => 'decimal:2',
        ];
    }
}
