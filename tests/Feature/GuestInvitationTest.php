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
 * @descripción Verifica el envío de invitaciones por correo con QR y la página pública del invitado.
 */

namespace Tests\Feature;

use App\Mail\GuestInvitation;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class GuestInvitationTest extends TestCase
{
    use RefreshDatabase;

    private function createEventWithGuest(?string $email): array
    {
        $user = User::factory()->create();
        $venue = Venue::query()->create([
            'name' => 'Salón del Valle',
            'address' => 'Av. Test 123, Toluca',
            'lat' => 19.2826,
            'lng' => -99.6557,
            'capacity' => 150,
            'min_price' => 15000,
            'max_price' => 30000,
            'google_maps_url' => 'https://maps.example/venue',
        ]);
        $event = $user->events()->create([
            'venue_id' => $venue->id,
            'name' => 'Boda de prueba',
            'category' => 'Boda',
            'event_date' => '2027-05-10',
            'guest_count' => 80,
            'total_budget' => 80000,
            'rules' => 'No se admiten niños.',
            'public_uuid' => (string) Str::uuid(),
        ]);
        $category = $event->guestCategories()->create([
            'name' => 'Familia',
            'guest_count' => 20,
            'priority' => 1,
        ]);
        $guest = $event->guests()->create([
            'name' => 'Ana Pérez',
            'email' => $email,
            'category_id' => $category->id,
            'qr_token' => (string) Str::uuid(),
        ]);

        return [$user, $event, $guest];
    }

    public function test_sends_invitation_with_personal_qr_to_a_guest_with_email(): void
    {
        Mail::fake();
        [$user, $event, $guest] = $this->createEventWithGuest('ana@example.com');
        $qrPng = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8AAAwAB/AF+gAAAAABJRU5ErkJggg==';

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/events/{$event->id}/guests/{$guest->id}/invitation", [
                'qr_png' => $qrPng,
            ])
            ->assertOk()
            ->assertJsonPath('email', 'ana@example.com')
            ->assertJsonPath('message', 'Invitación enviada correctamente.')
            ->assertJsonPath('invitation_url', route('public.invitations.show', ['qr_token' => $guest->qr_token]));

        Mail::assertSent(GuestInvitation::class, function (GuestInvitation $mail) use ($guest, $qrPng): bool {
            return $mail->hasTo('ana@example.com')
                && $mail->qrDataUrl === $qrPng
                && str_contains($mail->invitationUrl, $guest->qr_token)
                && $mail->guest->is($guest);
        });
    }

    public function test_rejects_invitation_when_guest_has_no_email(): void
    {
        Mail::fake();
        [$user, $event, $guest] = $this->createEventWithGuest(null);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/events/{$event->id}/guests/{$guest->id}/invitation", [
                'qr_png' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8AAAwAB/AF+gAAAAABJRU5ErkJggg==',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');

        Mail::assertNothingSent();
    }

    public function test_rejects_invalid_qr_payload(): void
    {
        Mail::fake();
        [$user, $event, $guest] = $this->createEventWithGuest('ana@example.com');

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/events/{$event->id}/guests/{$guest->id}/invitation", [
                'qr_png' => 'javascript:alert(1)',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('qr_png');

        Mail::assertNothingSent();
    }

    public function test_public_invitation_page_is_accessible_with_the_guest_token(): void
    {
        [, , $guest] = $this->createEventWithGuest('ana@example.com');

        $this->get("/invitacion/{$guest->qr_token}")
            ->assertOk()
            ->assertSee('GuestInvitation');
    }
}
