<?php

/**
 * @empresa Bigbitbyte
 *
 * @autor Ivan Hernandez Hinojosa
 *
 * @creado 20/noviembre/2026
 *
 * @modificado 26/noviembre/2026
 *
 * @descripción Representa a un invitado, su código QR, su silla y su respuesta a la invitación.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Guest extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_REJECTED = 'rejected';

    /** @var list<string> */
    protected $fillable = ['event_id', 'name', 'email', 'category_id', 'qr_token', 'invitation_status', 'table_id', 'seat_number'];

    /** @return list<string> */
    public static function statuses(): array
    {
        return [self::STATUS_PENDING, self::STATUS_CONFIRMED, self::STATUS_REJECTED];
    }

    public static function statusLabel(string $status): string
    {
        return match ($status) {
            self::STATUS_CONFIRMED => 'confirmo asistencia',
            self::STATUS_REJECTED => 'rechazo invitación',
            default => 'pendiente',
        };
    }

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

    /** @return BelongsTo<Table, $this> */
    public function table(): BelongsTo
    {
        return $this->belongsTo(Table::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'seat_number' => 'integer',
        ];
    }
}
