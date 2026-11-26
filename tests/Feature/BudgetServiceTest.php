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
 * @descripción Verifica el presupuesto disponible y las sugerencias de paquetes.
 */

use App\Models\Package;
use App\Models\User;
use App\Models\Venue;
use App\Services\BudgetService;
use Illuminate\Support\Str;

test('subtracts the minimum venue price from the event budget', function (): void {
    $user = User::factory()->create();
    $venue = Venue::query()->create([
        'name' => 'Salón de Prueba',
        'address' => 'Toluca, Estado de México',
        'lat' => 19.2826,
        'lng' => -99.6557,
        'capacity' => 200,
        'min_price' => 25000.25,
        'max_price' => 60000,
    ]);
    $event = $user->events()->create([
        'venue_id' => $venue->id,
        'name' => 'Evento de Prueba',
        'category' => 'Boda',
        'event_date' => '2027-05-10',
        'guest_count' => 100,
        'total_budget' => 100000.75,
        'public_uuid' => (string) Str::uuid(),
    ]);

    expect(app(BudgetService::class)->calculateAvailableBudget($event))->toBe(75000.5);
});

test('returns zero when the venue minimum exceeds the event budget', function (): void {
    $user = User::factory()->create();
    $venue = Venue::query()->create([
        'name' => 'Salón de Prueba',
        'address' => 'Toluca, Estado de México',
        'lat' => 19.2826,
        'lng' => -99.6557,
        'capacity' => 200,
        'min_price' => 25000,
        'max_price' => 60000,
    ]);
    $event = $user->events()->create([
        'venue_id' => $venue->id,
        'name' => 'Evento de Prueba',
        'category' => 'Boda',
        'event_date' => '2027-05-10',
        'guest_count' => 100,
        'total_budget' => 20000,
        'public_uuid' => (string) Str::uuid(),
    ]);

    expect(app(BudgetService::class)->calculateAvailableBudget($event))->toBe(0.0);
});

test('suggests only same-type packages within the available budget', function (): void {
    $user = User::factory()->create();
    $venue = Venue::query()->create([
        'name' => 'Salón de Prueba',
        'address' => 'Toluca, Estado de México',
        'lat' => 19.2826,
        'lng' => -99.6557,
        'capacity' => 200,
        'min_price' => 25000,
        'max_price' => 60000,
    ]);
    $event = $user->events()->create([
        'venue_id' => $venue->id,
        'name' => 'Boda de Prueba',
        'category' => 'Boda',
        'event_date' => '2027-05-10',
        'guest_count' => 100,
        'total_budget' => 50000,
        'public_uuid' => (string) Str::uuid(),
    ]);
    $matchingPackage = Package::query()->create([
        'category' => 'Boda',
        'name' => 'Paquete de Boda',
        'estimated_price' => 20000,
    ]);
    $overBudgetPackage = Package::query()->create([
        'category' => 'Boda',
        'name' => 'Paquete de Boda Costoso',
        'estimated_price' => 30000,
    ]);

    $birthdayEvent = $user->events()->create([
        'name' => 'Cumpleaños de Prueba',
        'category' => 'Cumpleaños',
        'event_date' => '2027-06-15',
        'guest_count' => 50,
        'total_budget' => 30000,
        'public_uuid' => (string) Str::uuid(),
    ]);
    $otherTypePackage = Package::query()->create([
        'category' => 'Cumpleaños',
        'name' => 'Paquete de Cumpleaños',
        'estimated_price' => 10000,
    ]);

    $suggestedPackages = app(BudgetService::class)->suggestPackage($event);

    expect($suggestedPackages->modelKeys())->toBe([$matchingPackage->id]);
});

test('subtracts paid packages from the available budget', function (): void {
    $user = User::factory()->create();
    $event = $user->events()->create([
        'name' => 'Evento con paquete pagado',
        'category' => 'Boda',
        'event_date' => '2027-05-10',
        'guest_count' => 100,
        'total_budget' => 60000,
        'public_uuid' => (string) Str::uuid(),
    ]);
    $event->packages()->create([
        'category' => 'Boda',
        'name' => 'Paquete pagado',
        'estimated_price' => 15000,
        'is_paid' => true,
    ]);
    $event->packages()->create([
        'category' => 'Boda',
        'name' => 'Paquete pendiente',
        'estimated_price' => 20000,
        'is_paid' => false,
    ]);

    expect(app(BudgetService::class)->calculateAvailableBudget($event))->toBe(45000.0);
});
