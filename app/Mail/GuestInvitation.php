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
 * @descripción Envía por correo la invitación personal de un invitado con su código QR.
 */

namespace App\Mail;

use App\Models\Guest;
use Illuminate\Mail\Mailable;

class GuestInvitation extends Mailable
{
    /**
     * Crea el correo con la invitación personal y su código QR.
     */
    public function __construct(
        public Guest $guest,
        public string $invitationUrl,
        public string $qrDataUrl,
    ) {}

    /**
     * Configura el asunto y la vista del correo.
     */
    public function build(): self
    {
        $event = $this->guest->event;
        $venue = $event->venue;

        return $this->subject('Tu invitación para '.$event->name)
            ->view('emails.guest-invitation')
            ->with([
                'guestName' => $this->guest->name,
                'eventName' => $event->name,
                'eventDate' => $event->event_date?->format('d/m/Y') ?? 'Por definir',
                'venueName' => $venue?->name ?? 'Por definir',
                'venueAddress' => $venue?->address ?? 'Dirección por definir',
                'rules' => $event->rules,
                'invitationUrl' => $this->invitationUrl,
                'qrDataUrl' => $this->qrDataUrl,
            ]);
    }
}
