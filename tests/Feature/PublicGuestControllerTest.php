<?php

/**
 * @empresa Bigbitbyte
 *
 * @autor Ivan Hernandez Hinojosa
 *
 * @creado 20/noviembre/2026
 *
 * @modificado 25/noviembre/2026
 *
 * @descripción Verifica el endpoint público de invitaciones y sillas reservadas por token QR.
 */

namespace Tests\Feature;

use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PublicGuestControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_token_returns_guest_event_and_venue_details_without_authentication(): void
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
            'event_date' => '2026-12-12',
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
            'email' => null,
            'category_id' => $category->id,
            'qr_token' => (string) Str::uuid(),
        ]);
        $table = $event->tables()->create([
            'table_number' => 3,
            'capacity' => 8,
            'shape' => 'round',
            'width' => 76,
            'height' => 76,
            'position_x' => 200,
            'position_y' => 300,
        ]);
        $guest->update(['table_id' => $table->id, 'seat_number' => 4]);

        $this->getJson("/api/public/guest/{$guest->qr_token}")
            ->assertOk()
            ->assertJsonPath('guest.name', 'Ana Pérez')
            ->assertJsonPath('guest.category', 'Familia')
            ->assertJsonPath('guest.invitation_status', 'pending')
            ->assertJsonPath('guest.invitation_status_label', 'pendiente')
            ->assertJsonPath('reservation.table_number', 3)
            ->assertJsonPath('reservation.seat_number', 4)
            ->assertJsonPath('reservation.table.shape', 'round')
            ->assertJsonPath('reservation.table.position_x', 200)
            ->assertJsonPath('event.name', 'Boda de prueba')
            ->assertJsonPath('event.date', '2026-12-12')
            ->assertJsonPath('event.rules', 'No se admiten niños.')
            ->assertJsonPath('venue.name', 'Salón del Valle')
            ->assertJsonPath('venue.address', 'Av. Test 123, Toluca')
            ->assertJsonPath('venue.lat', 19.2826)
            ->assertJsonPath('venue.lng', -99.6557)
            ->assertJsonPath('venue.google_maps_url', 'https://maps.example/venue');
    }

    public function test_missing_public_token_returns_controlled_not_found_response(): void
    {
        $this->getJson('/api/public/guest/'.Str::uuid())
            ->assertNotFound()
            ->assertJsonPath('message', 'Invitación no encontrada');
    }

    public function test_public_event_uuid_returns_event_details_without_authentication(): void
    {
        $user = User::factory()->create();
        $event = $user->events()->create([
            'name' => 'Boda de prueba',
            'category' => 'Boda',
            'event_date' => '2026-12-12',
            'guest_count' => 40,
            'total_budget' => 40000,
            'public_uuid' => (string) Str::uuid(),
        ]);
        $event->guestCategories()->create([
            'name' => 'Familia',
            'guest_count' => 40,
            'priority' => 1,
        ]);
        $event->eventSpaces()->create([
            'name' => 'Bar',
            'type' => 'bar',
            'width' => 180,
            'height' => 90,
            'position_x' => 100,
            'position_y' => 200,
        ]);

        $this->getJson('/api/public/events/'.strtoupper($event->public_uuid))
            ->assertOk()
            ->assertJsonPath('event.id', $event->id)
            ->assertJsonPath('event.public_uuid', $event->public_uuid)
            ->assertJsonPath('event.name', 'Boda de prueba')
            ->assertJsonPath('spaces.0.type', 'bar')
            ->assertJsonPath('spaces.0.position_x', 100)
            ->assertJsonPath('guest_categories.0.name', 'Familia');
    }

    public function test_public_token_returns_null_reservation_when_guest_has_no_assigned_seat(): void
    {
        $user = User::factory()->create();
        $event = $user->events()->create([
            'name' => 'Boda sin sillas',
            'category' => 'Boda',
            'event_date' => '2026-12-12',
            'guest_count' => 10,
            'total_budget' => 10000,
            'public_uuid' => (string) Str::uuid(),
        ]);
        $category = $event->guestCategories()->create([
            'name' => 'Amigos',
            'guest_count' => 10,
            'priority' => 2,
        ]);
        $guest = $event->guests()->create([
            'name' => 'Ana Pérez',
            'email' => null,
            'category_id' => $category->id,
            'qr_token' => (string) Str::uuid(),
        ]);

        $this->getJson("/api/public/guest/{$guest->qr_token}")
            ->assertOk()
            ->assertJsonPath('reservation', null);
    }
}
