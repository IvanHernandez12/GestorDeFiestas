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
 * @descripción Representa un paquete dinámico asociado a un evento.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Package extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'event_id',
        'category',
        'name',
        'description',
        'estimated_price',
        'is_paid',
        'payment_reference',
        'mercadopago_preference_id',
        'mercadopago_payment_id',
    ];

    /** @return BelongsTo<Event, $this> */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /** @return BelongsToMany<Provider, $this> */
    public function providers(): BelongsToMany
    {
        return $this->belongsToMany(Provider::class)->withPivot('price')->withTimestamps();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'estimated_price' => 'decimal:2',
            'is_paid' => 'boolean',
        ];
    }
}
