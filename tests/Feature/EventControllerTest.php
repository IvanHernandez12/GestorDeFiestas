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
 * @descripción Prueba el listado, creación y acceso a eventos por usuario.
 */

use App\Models\Plan;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Support\Str;

test('creates an event with a unique public UUID', function (): void {
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
        'guest_count' => 50,
        'total_budget' => 40000,
        'venue_id' => $venue->id,
    ]);

    $response->assertCreated()
        ->assertJsonPath('name', 'Boda de Prueba')
        ->assertJsonPath('venue_id', $venue->id);

    $publicUuid = $response->json('public_uuid');
    expect(Str::isUuid($publicUuid))->toBeTrue();
    $this->assertDatabaseHas('events', [
        'user_id' => $user->id,
        'venue_id' => $venue->id,
        'public_uuid' => $publicUuid,
        'guest_count' => 50,
    ]);
    $this->assertDatabaseHas('guest_categories', [
        'event_id' => $response->json('id'),
        'name' => 'Categoría General',
        'guest_count' => 50,
    ]);
});

test('fills the remaining guests into a general category and distributes all guests', function (): void {
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
        'name' => 'Evento con categorías parciales',
        'category' => 'Boda',
        'event_date' => '2027-05-10',
        'guest_count' => 50,
        'total_budget' => 40000,
        'venue_id' => $venue->id,
        'guest_categories' => [
            ['name' => 'Categoría 1', 'guest_count' => 15],
            ['name' => 'Categoría 2', 'guest_count' => 15],
        ],
    ]);

    $response->assertCreated()
        ->assertJsonPath('guest_categories.2.name', 'Categoría General')
        ->assertJsonPath('guest_categories.2.guest_count', 20);

    expect(array_sum(array_column($response->json('tables'), 'capacity')))->toBe(50);
    $this->assertDatabaseHas('guest_categories', [
        'event_id' => $response->json('id'),
        'name' => 'Categoría General',
        'guest_count' => 20,
    ]);
});

test('rejects categories whose total exceeds the event guest count', function (): void {
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

    $this->actingAs($user, 'sanctum')->postJson('/api/events', [
        'name' => 'Evento con exceso de categorías',
        'category' => 'Boda',
        'event_date' => '2027-05-10',
        'guest_count' => 30,
        'total_budget' => 40000,
        'venue_id' => $venue->id,
        'guest_categories' => [
            ['name' => 'Categoría 1', 'guest_count' => 20],
            ['name' => 'Categoría 2', 'guest_count' => 15],
        ],
    ])->assertUnprocessable()
        ->assertJsonPath('errors.guest_categories.0', 'La suma de invitados por categoría no puede exceder el total del evento.');

    $this->assertDatabaseCount('events', 0);
});

test('requires the event date to be at least 15 days in the future', function (): void {
    $this->travelTo(now()->setDate(2026, 11, 24)->setTime(12, 0));
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

    $this->actingAs($user, 'sanctum')->postJson('/api/events', [
        'name' => 'Evento con fecha cercana',
        'category' => 'Boda',
        'event_date' => '2026-12-08',
        'guest_count' => 30,
        'total_budget' => 40000,
        'venue_id' => $venue->id,
    ])->assertUnprocessable()
        ->assertJsonPath('errors.event_date.0', 'La fecha debe ser al menos 15 días después de hoy');

    $this->assertDatabaseCount('events', 0);
});

test('lists only the authenticated user events', function (): void {
    $plan = Plan::query()->create([
        'name' => 'Gratuito',
        'slug' => 'free',
        'price' => 0,
        'max_events' => 1,
        'max_guests' => 50,
        'is_free' => true,
    ]);
    $user = User::factory()->for($plan)->create();
    $anotherUser = User::factory()->create();

    $ownedEvent = $user->events()->create([
        'name' => 'Mi Evento',
        'category' => 'Boda',
        'event_date' => '2027-05-10',
        'guest_count' => 30,
        'total_budget' => 25000,
        'public_uuid' => (string) Str::uuid(),
    ]);
    $anotherUser->events()->create([
        'name' => 'Evento Ajeno',
        'category' => 'Cumpleaños',
        'event_date' => '2027-06-15',
        'guest_count' => 20,
        'total_budget' => 10000,
        'public_uuid' => (string) Str::uuid(),
    ]);

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/events')
        ->assertOk()
        ->assertJsonCount(1)
        ->assertJsonPath('0.id', $ownedEvent->id)
        ->assertJsonPath('0.name', 'Mi Evento');
});

test('shows an event owned by the authenticated user', function (): void {
    $user = User::factory()->create();
    $event = $user->events()->create([
        'name' => 'Evento Propio',
        'category' => 'Boda',
        'event_date' => '2027-05-10',
        'guest_count' => 30,
        'total_budget' => 25000,
        'public_uuid' => (string) Str::uuid(),
    ]);

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/events/'.$event->id)
        ->assertOk()
        ->assertJsonPath('id', $event->id)
        ->assertJsonPath('name', 'Evento Propio');
});

test('returns not found when the event belongs to another user', function (): void {
    $owner = User::factory()->create();
    $anotherUser = User::factory()->create();
    $event = $owner->events()->create([
        'name' => 'Evento Privado',
        'category' => 'Boda',
        'event_date' => '2027-05-10',
        'guest_count' => 30,
        'total_budget' => 25000,
        'public_uuid' => (string) Str::uuid(),
    ]);

    $this->actingAs($anotherUser, 'sanctum')
        ->getJson('/api/events/'.$event->id)
        ->assertNotFound();
});

test('requires authentication to list events', function (): void {
    $this->getJson('/api/events')->assertUnauthorized();
});
