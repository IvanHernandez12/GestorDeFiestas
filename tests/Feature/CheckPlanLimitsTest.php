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
use Database\Seeders\PlanSeeder;
use Illuminate\Support\Str;

test('seeded plans define the expected event and guest limits', function (): void {
    $this->seed(PlanSeeder::class);

    expect([
        'free' => Plan::query()->where('slug', 'free')->firstOrFail()->only('max_events', 'max_guests'),
        'basic' => Plan::query()->where('slug', 'basic')->firstOrFail()->only('max_events', 'max_guests'),
        'premium' => Plan::query()->where('slug', 'premium')->firstOrFail()->only('max_events', 'max_guests'),
    ])->toMatchArray([
        'free' => ['max_events' => 1, 'max_guests' => 50],
        'basic' => ['max_events' => 5, 'max_guests' => 200],
        'premium' => ['max_events' => null, 'max_guests' => 1000],
    ]);
});

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

function createPlanLimitVenue(int $capacity = 300, float $minimumPrice = 5000): Venue
{
    return Venue::query()->create([
        'name' => 'Salón de Prueba '.$capacity,
        'address' => 'Toluca, Estado de México',
        'lat' => 19.2826,
        'lng' => -99.6557,
        'capacity' => $capacity,
        'min_price' => $minimumPrice,
        'max_price' => $minimumPrice * 4,
    ]);
}

function createPlanFixture(string $name, string $slug, int $price, ?int $maxEvents, ?int $maxGuests, bool $isFree): Plan
{
    return Plan::query()->create([
        'name' => $name,
        'slug' => $slug,
        'price' => $price,
        'max_events' => $maxEvents,
        'max_guests' => $maxGuests,
        'is_free' => $isFree,
    ]);
}

function createExistingEvent(User $user, int $index): void
{
    Event::query()->create([
        'user_id' => $user->id,
        'name' => "Evento existente {$index}",
        'category' => 'Boda',
        'event_date' => now()->addDays(30)->toDateString(),
        'guest_count' => 20,
        'total_budget' => 10000,
        'public_uuid' => (string) Str::uuid(),
    ]);
}

test('forbids a basic user after five total events', function (): void {
    $plan = createPlanFixture('Básico', 'basic', 299, 5, 200, false);
    $user = User::factory()->for($plan)->create();

    foreach (range(1, 5) as $index) {
        createExistingEvent($user, $index);
    }

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/events', ['guest_count' => 20])
        ->assertForbidden()
        ->assertJsonPath('message', 'Tu plan permite crear 5 eventos.');

    $this->assertDatabaseCount('events', 5);
});

test('allows a basic user to create exactly five total events', function (): void {
    $plan = createPlanFixture('Básico', 'basic', 299, 5, 200, false);
    $user = User::factory()->for($plan)->create();
    $venue = createPlanLimitVenue();

    foreach (range(1, 4) as $index) {
        createExistingEvent($user, $index);
    }

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/events', [
            'name' => 'Quinto evento',
            'category' => 'Boda',
            'event_date' => '2027-05-10',
            'guest_count' => 100,
            'total_budget' => 80000,
            'venue_id' => $venue->id,
        ])
        ->assertCreated();

    $this->assertDatabaseCount('events', 5);
});

test('forbids a basic user who requests more than 200 guests', function (): void {
    $plan = createPlanFixture('Básico', 'basic', 299, 5, 200, false);
    $user = User::factory()->for($plan)->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/events', ['guest_count' => 201])
        ->assertForbidden()
        ->assertJsonPath('message', 'Tu plan permite hasta 200 invitados por evento.');

    $this->assertDatabaseCount('events', 0);
});

test('allows a basic user who requests exactly 200 guests', function (): void {
    $plan = createPlanFixture('Básico', 'basic', 299, 5, 200, false);
    $user = User::factory()->for($plan)->create();
    $venue = createPlanLimitVenue();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/events', [
            'name' => 'Evento de 200 invitados',
            'category' => 'Boda',
            'event_date' => '2027-05-10',
            'guest_count' => 200,
            'total_budget' => 80000,
            'venue_id' => $venue->id,
        ])
        ->assertCreated()
        ->assertJsonPath('guest_count', 200);
});

test('allows a premium user beyond the basic event limit', function (): void {
    $plan = createPlanFixture('Premium', 'premium', 699, null, 1000, false);
    $user = User::factory()->for($plan)->create();
    $venue = createPlanLimitVenue(1200, 10000);

    foreach (range(1, 5) as $index) {
        createExistingEvent($user, $index);
    }

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/events', [
            'name' => 'Sexto evento premium',
            'category' => 'Boda',
            'event_date' => '2027-05-10',
            'guest_count' => 500,
            'total_budget' => 200000,
            'venue_id' => $venue->id,
        ])
        ->assertCreated();

    $this->assertDatabaseCount('events', 6);
});

test('forbids a premium user who requests more than 1000 guests', function (): void {
    $plan = createPlanFixture('Premium', 'premium', 699, null, 1000, false);
    $user = User::factory()->for($plan)->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/events', ['guest_count' => 1001])
        ->assertForbidden()
        ->assertJsonPath('message', 'Tu plan permite hasta 1000 invitados por evento.');

    $this->assertDatabaseCount('events', 0);
});
