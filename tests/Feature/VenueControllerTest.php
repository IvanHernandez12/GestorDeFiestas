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
 * @descripción Prueba la búsqueda pública de salones mediante filtros.
 */

use App\Models\Venue;
use Database\Seeders\VenueSeeder;

test('filters venues by capacity, overlapping price range, and name', function (): void {
    $createVenue = function (array $attributes): Venue {
        return Venue::query()->create([
            'address' => 'Toluca, Estado de México',
            'lat' => 19.2826,
            'lng' => -99.6557,
            ...$attributes,
        ]);
    };

    $matchingVenue = $createVenue([
        'name' => 'Jardín Encanto',
        'capacity' => 250,
        'min_price' => 20000,
        'max_price' => 50000,
    ]);
    $createVenue([
        'name' => 'Jardín Encanto Pequeño',
        'capacity' => 100,
        'min_price' => 20000,
        'max_price' => 50000,
    ]);
    $createVenue([
        'name' => 'Jardín Encanto Económico',
        'capacity' => 250,
        'min_price' => 7000,
        'max_price' => 15000,
    ]);
    $createVenue([
        'name' => 'Jardín Encanto Premium',
        'capacity' => 250,
        'min_price' => 60000,
        'max_price' => 90000,
    ]);
    $createVenue([
        'name' => 'Centro Primavera',
        'capacity' => 250,
        'min_price' => 20000,
        'max_price' => 50000,
    ]);

    $this->getJson('/api/venues/search?capacity=200&min_price=20000&max_price=55000&name=Jard%C3%ADn%20Encanto')
        ->assertOk()
        ->assertJsonCount(1)
        ->assertJsonPath('0.id', $matchingVenue->id)
        ->assertJsonPath('0.name', 'Jardín Encanto');
});

test('returns all venues when no search filters are provided', function (): void {
    Venue::query()->create([
        'name' => 'Salón Norte',
        'address' => 'Toluca, Estado de México',
        'lat' => 19.2826,
        'lng' => -99.6557,
        'capacity' => 100,
        'min_price' => 7000,
        'max_price' => 25000,
    ]);

    Venue::query()->create([
        'name' => 'Jardín Sur',
        'address' => 'Toluca, Estado de México',
        'lat' => 19.2925,
        'lng' => -99.6362,
        'capacity' => 200,
        'min_price' => 20000,
        'max_price' => 60000,
    ]);

    $this->getJson('/api/venues/search')
        ->assertOk()
        ->assertJsonCount(2);
});

test('rejects an invalid venue price range', function (): void {
    $this->getJson('/api/venues/search?min_price=50000&max_price=20000')
        ->assertUnprocessable()
        ->assertJsonValidationErrors('max_price');
});

test('matches the search term against venue name or address', function (): void {
    Venue::query()->create([
        'name' => 'Salón Jardín Central',
        'address' => 'Metepec, Estado de México',
        'lat' => 19.2826,
        'lng' => -99.6557,
        'capacity' => 100,
        'min_price' => 10000,
        'max_price' => 10000,
    ]);
    Venue::query()->create([
        'name' => 'Metepec Eventos',
        'address' => 'Toluca, Estado de México',
        'lat' => 19.2925,
        'lng' => -99.6362,
        'capacity' => 100,
        'min_price' => 12000,
        'max_price' => 12000,
    ]);
    Venue::query()->create([
        'name' => 'Salón Jardín Norte',
        'address' => 'Toluca, Estado de México',
        'lat' => 19.3000,
        'lng' => -99.6400,
        'capacity' => 100,
        'min_price' => 15000,
        'max_price' => 15000,
    ]);

    $this->getJson('/api/venues/search?name=Metepec')
        ->assertOk()
        ->assertJsonCount(2)
        ->assertJsonFragment(['name' => 'Salón Jardín Central'])
        ->assertJsonFragment(['name' => 'Metepec Eventos']);
});

test('replaces venue seed data with the ten requested venues', function (): void {
    Venue::query()->create([
        'name' => 'Salón Antiguo',
        'address' => 'Toluca, Estado de México',
        'lat' => 19.2826,
        'lng' => -99.6557,
        'capacity' => 100,
        'min_price' => 10000,
        'max_price' => 10000,
    ]);

    $this->seed(VenueSeeder::class);

    expect(Venue::query()->count())->toBe(10);
    $this->assertDatabaseHas('venues', [
        'name' => 'Salon Altezz',
        'capacity' => 180,
        'min_price' => 28000,
        'max_price' => 28000,
    ]);
    $this->assertDatabaseMissing('venues', ['name' => 'Salón Antiguo']);
});
