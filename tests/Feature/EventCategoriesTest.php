<?php

/**
 * @empresa Bigbitbyte
 *
 * @autor Ivan Hernandez Hinojosa
 *
 * @creado 14/noviembre/2026
 *
 * @modificado 24/noviembre/2026
 *
 * @descripción Comprueba el reglamento y la distribución por categorías al crear eventos.
 */

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventCategoriesTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_payload_saves_rules_categories_and_distributed_tables(): void
    {
        $plan = Plan::query()->create([
            'name' => 'Gratuito',
            'slug' => 'free',
            'price' => 0,
            'max_events' => 1,
            'max_guests' => 50,
            'is_free' => true,
        ]);
        $user = User::factory()->for($plan)->create();
        $venue = Venue::query()->create([
            'name' => 'Jardín de Prueba',
            'address' => 'Toluca, Estado de México',
            'lat' => 19.2826,
            'lng' => -99.6557,
            'capacity' => 100,
            'min_price' => 7000,
            'max_price' => 25000,
        ]);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/events', [
            'name' => 'Boda de Prueba',
            'category' => 'Boda',
            'event_date' => '2027-05-10',
            'guest_count' => 6,
            'total_budget' => 40000,
            'venue_id' => $venue->id,
            'rules' => 'No se admiten niños.',
            'guest_categories' => [
                ['name' => 'Categoría 1', 'guest_count' => 4],
                ['name' => 'Categoría 2', 'guest_count' => 2],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('rules', 'No se admiten niños.')
            ->assertJsonPath('guest_categories.0.priority', 1)
            ->assertJsonPath('guest_categories.0.guest_count', 4)
            ->assertJsonPath('guest_categories.1.priority', 2)
            ->assertJsonCount(2, 'tables');

        $eventId = $response->json('id');
        $this->assertDatabaseHas('events', [
            'id' => $eventId,
            'rules' => 'No se admiten niños.',
        ]);
        $this->assertDatabaseHas('guest_categories', [
            'event_id' => $eventId,
            'name' => 'Categoría 2',
            'guest_count' => 2,
            'priority' => 2,
        ]);
    }

    public function test_category_guest_counts_are_completed_by_a_general_category(): void
    {
        $plan = Plan::query()->create([
            'name' => 'Gratuito',
            'slug' => 'free',
            'price' => 0,
            'max_events' => 1,
            'max_guests' => 50,
            'is_free' => true,
        ]);
        $user = User::factory()->for($plan)->create();
        $venue = Venue::query()->create([
            'name' => 'Jardín de Prueba',
            'address' => 'Toluca, Estado de México',
            'lat' => 19.2826,
            'lng' => -99.6557,
            'capacity' => 100,
            'min_price' => 7000,
            'max_price' => 25000,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/events', [
                'name' => 'Boda de Prueba',
                'category' => 'Boda',
                'event_date' => '2027-05-10',
                'guest_count' => 6,
                'total_budget' => 40000,
                'venue_id' => $venue->id,
                'guest_categories' => [
                    ['name' => 'Categoría 1', 'guest_count' => 4],
                    ['name' => 'Categoría 2', 'guest_count' => 1],
                ],
            ]);

        $response->assertCreated()
            ->assertJsonPath('guest_categories.2.name', 'Categoría General')
            ->assertJsonPath('guest_categories.2.guest_count', 1)
            ->assertJsonCount(3, 'tables');

        $this->assertDatabaseCount('events', 1);
        $this->assertDatabaseHas('guest_categories', [
            'event_id' => $response->json('id'),
            'name' => 'Categoría General',
            'guest_count' => 1,
        ]);
    }
}
