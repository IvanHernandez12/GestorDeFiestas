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
 * @descripción Verifica los límites de eventos e invitados de los planes.
 */

use App\Models\Event;
use App\Models\Plan;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Support\Str;

test('forbids a free user who already has one event with status 403', function (): void {
    $plan = Plan::query()->create([
        'name' => 'Gratuito',
        'slug' => 'free',
        'price' => 0,
        'max_events' => 1,
        'max_guests' => 50,
        'is_free' => true,
    ]);
    $user = User::factory()->for($plan)->create();

    Event::query()->create([
        'user_id' => $user->id,
        'name' => 'Evento existente',
        'category' => 'Boda',
        'event_date' => now()->toDateString(),
        'guest_count' => 25,
        'total_budget' => 10000,
        'public_uuid' => (string) Str::uuid(),
    ]);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/events', ['guest_count' => 25])
        ->assertForbidden()
        ->assertJsonPath('message', 'Has alcanzado el límite de tu plan gratuito: solo puedes crear 1 evento.');

    $this->assertDatabaseCount('events', 1);
});

test('forbids a free user who requests more than 50 guests with status 403', function (): void {
    $plan = Plan::query()->create([
        'name' => 'Gratuito',
        'slug' => 'free',
        'price' => 0,
        'max_events' => 1,
        'max_guests' => 50,
        'is_free' => true,
    ]);
    $user = User::factory()->for($plan)->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/events', ['guest_count' => 51])
        ->assertForbidden()
        ->assertJsonPath('message', 'Has alcanzado el límite de tu plan gratuito: máximo 50 invitados por evento.');

    $this->assertDatabaseCount('events', 0);
});

test('allows a free user to request exactly 50 guests', function (): void {
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
        'name' => 'Salón de Prueba',
        'address' => 'Toluca, Estado de México',
        'lat' => 19.2826,
        'lng' => -99.6557,
        'capacity' => 100,
        'min_price' => 7000,
        'max_price' => 25000,
    ]);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/events', [
            'name' => 'Evento de 50 invitados',
            'category' => 'Cumpleaños',
            'event_date' => '2027-05-10',
            'guest_count' => 50,
            'total_budget' => 30000,
            'venue_id' => $venue->id,
        ])
        ->assertCreated()
        ->assertJsonPath('guest_count', 50);

    $this->assertDatabaseCount('events', 1);
});

test('does not apply free plan limits to a paid user', function (): void {
    $plan = Plan::query()->create([
        'name' => 'Profesional',
        'slug' => 'professional',
        'price' => 999,
        'max_events' => null,
        'max_guests' => null,
        'is_free' => false,
    ]);
    $user = User::factory()->for($plan)->create();
    $venue = Venue::query()->create([
        'name' => 'Salón de Prueba',
        'address' => 'Toluca, Estado de México',
        'lat' => 19.2826,
        'lng' => -99.6557,
        'capacity' => 400,
        'min_price' => 50000,
        'max_price' => 200000,
    ]);

    Event::query()->create([
        'user_id' => $user->id,
        'name' => 'Evento existente',
        'category' => 'Boda',
        'event_date' => now()->toDateString(),
        'guest_count' => 50,
        'total_budget' => 10000,
        'public_uuid' => (string) Str::uuid(),
    ]);
    Event::query()->create([
        'user_id' => $user->id,
        'name' => 'Segundo evento',
        'category' => 'Cumpleaños',
        'event_date' => now()->toDateString(),
        'guest_count' => 80,
        'total_budget' => 20000,
        'public_uuid' => (string) Str::uuid(),
    ]);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/events', [
            'name' => 'Evento de pago',
            'category' => 'Boda',
            'event_date' => '2027-05-10',
            'guest_count' => 100,
            'total_budget' => 80000,
            'venue_id' => $venue->id,
        ])
        ->assertCreated();

    $this->assertDatabaseCount('events', 3);
});

test('returns unauthorized when creating an event without a token', function (): void {
    $this->postJson('/api/events', ['guest_count' => 20])->assertUnauthorized();
});
