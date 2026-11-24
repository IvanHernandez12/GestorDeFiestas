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
 * @descripción Verifica el endpoint público de invitaciones por token QR.
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

        $this->getJson("/api/public/guest/{$guest->qr_token}")
            ->assertOk()
            ->assertJsonPath('guest.name', 'Ana Pérez')
            ->assertJsonPath('guest.category', 'Familia')
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
}
