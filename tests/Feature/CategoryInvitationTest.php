<?php

/**
 * @empresa Bigbitbyte
 *
 * @autor Ivan Hernandez Hinojosa
 *
 * @creado 26/noviembre/2026
 *
 * @modificado 26/noviembre/2026
 *
 * @descripción Verifica las invitaciones grupales por categoría y el seguimiento de respuestas del organizador.
 */

namespace Tests\Feature;

use App\Models\Guest;
use App\Models\GuestCategory;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CategoryInvitationTest extends TestCase
{
    use RefreshDatabase;

    private function createCategoryFixture(): array
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
            'public_uuid' => (string) Str::uuid(),
        ]);
        $otherCategory = $event->guestCategories()->create([
            'name' => 'Amigos',
            'guest_count' => 10,
            'priority' => 2,
            'public_uuid' => (string) Str::uuid(),
        ]);
        $pendingGuest = $event->guests()->create([
            'name' => 'Ana Pérez',
            'category_id' => $category->id,
            'qr_token' => (string) Str::uuid(),
        ]);
        $confirmedGuest = $event->guests()->create([
            'name' => 'Beto López',
            'category_id' => $category->id,
            'qr_token' => (string) Str::uuid(),
            'invitation_status' => Guest::STATUS_CONFIRMED,
        ]);
        $rejectedGuest = $event->guests()->create([
            'name' => 'Carla Ruiz',
            'category_id' => $category->id,
            'qr_token' => (string) Str::uuid(),
            'invitation_status' => Guest::STATUS_REJECTED,
        ]);
        $outsideGuest = $event->guests()->create([
            'name' => 'Diego Soto',
            'category_id' => $otherCategory->id,
            'qr_token' => (string) Str::uuid(),
        ]);

        return [$user, $event, $category, $pendingGuest, $confirmedGuest, $rejectedGuest, $outsideGuest];
    }

    public function test_category_endpoint_returns_guests_with_status_counts(): void
    {
        [, , $category] = $this->createCategoryFixture();

        $this->getJson("/api/public/categories/{$category->public_uuid}")
            ->assertOk()
            ->assertJsonPath('category.name', 'Familia')
            ->assertJsonPath('category.total', 3)
            ->assertJsonPath('event.name', 'Boda de prueba')
            ->assertJsonPath('venue.name', 'Salón del Valle')
            ->assertJsonPath('status_counts.pending', 1)
            ->assertJsonPath('status_counts.confirmed', 1)
            ->assertJsonPath('status_counts.rejected', 1)
            ->assertJsonPath('status_labels.confirmed', 'confirmo asistencia')
            ->assertJsonPath('status_labels.rejected', 'rechazo invitación')
            ->assertJsonPath('guests.0.status_label', 'pendiente');
    }

    public function test_guest_can_confirm_the_group_invitation(): void
    {
        [, , $category, $pendingGuest] = $this->createCategoryFixture();

        $this->postJson("/api/public/categories/{$category->public_uuid}/responses", [
            'guest_id' => $pendingGuest->id,
            'status' => Guest::STATUS_CONFIRMED,
        ])
            ->assertOk()
            ->assertJsonPath('message', 'Respuesta registrada.')
            ->assertJsonPath('guest.status', 'confirmed')
            ->assertJsonPath('guest.status_label', 'confirmo asistencia')
            ->assertJsonPath('status_counts.pending', 0)
            ->assertJsonPath('status_counts.confirmed', 2);

        $this->assertSame(Guest::STATUS_CONFIRMED, $pendingGuest->fresh()->invitation_status);
    }

    public function test_guest_can_reject_the_group_invitation(): void
    {
        [, , $category, $pendingGuest] = $this->createCategoryFixture();

        $this->postJson("/api/public/categories/{$category->public_uuid}/responses", [
            'guest_id' => $pendingGuest->id,
            'status' => Guest::STATUS_REJECTED,
        ])
            ->assertOk()
            ->assertJsonPath('guest.status_label', 'rechazo invitación')
            ->assertJsonPath('status_counts.pending', 0)
            ->assertJsonPath('status_counts.rejected', 2);

        $this->assertSame(Guest::STATUS_REJECTED, $pendingGuest->fresh()->invitation_status);
    }

    public function test_response_rejects_a_guest_from_another_category(): void
    {
        [, , $category, , , , $outsideGuest] = $this->createCategoryFixture();

        $this->postJson("/api/public/categories/{$category->public_uuid}/responses", [
            'guest_id' => $outsideGuest->id,
            'status' => Guest::STATUS_CONFIRMED,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('guest_id');
    }

    public function test_response_rejects_an_invalid_status(): void
    {
        [, , $category, $pendingGuest] = $this->createCategoryFixture();

        $this->postJson("/api/public/categories/{$category->public_uuid}/responses", [
            'guest_id' => $pendingGuest->id,
            'status' => 'maybe',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');
    }

    public function test_organizer_sees_invitation_status_in_guest_list(): void
    {
        [$user, $event] = $this->createCategoryFixture();

        $this->actingAs($user, 'sanctum')
            ->getJson("/api/events/{$event->id}/guests")
            ->assertOk()
            ->assertJsonPath('guests.0.name', 'Ana Pérez')
            ->assertJsonPath('guests.0.invitation_status', 'pending')
            ->assertJsonPath('guests.1.invitation_status', 'confirmed')
            ->assertJsonPath('guests.2.invitation_status', 'rejected');
    }

    public function test_public_category_invitation_page_is_accessible(): void
    {
        [, , $category] = $this->createCategoryFixture();

        $this->get("/invitacion/categoria/{$category->public_uuid}")
            ->assertOk()
            ->assertSee('CategoryInvitation');
    }

    public function test_created_categories_receive_a_public_uuid(): void
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

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/events', [
                'name' => 'Evento con categorías',
                'category' => 'Boda',
                'event_date' => now()->addDays(30)->toDateString(),
                'guest_count' => 10,
                'total_budget' => 20000,
                'venue_id' => $venue->id,
                'guest_categories' => [
                    ['name' => 'Familia', 'guest_count' => 6, 'priority' => 1],
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('guest_categories.0.name', 'Familia');

        $category = GuestCategory::query()->firstWhere('name', 'Familia');

        $this->assertNotNull($category->public_uuid);
    }
}
