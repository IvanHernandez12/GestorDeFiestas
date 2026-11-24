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
 * @descripción Comprueba recomendaciones y confirmación de paquetes para eventos.
 */

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Package;
use App\Models\Provider;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class EventPackageControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_recommended_packages_include_provider_prices_within_available_budget(): void
    {
        $user = User::factory()->create();
        $venue = Venue::query()->create([
            'name' => 'Salón de Prueba',
            'address' => 'Toluca, Estado de México',
            'lat' => 19.2826,
            'lng' => -99.6557,
            'capacity' => 200,
            'min_price' => 20000,
            'max_price' => 60000,
        ]);
        $event = $this->createEvent($user, $venue, 'Boda', 100000);
        $package = Package::query()->create([
            'category' => 'Boda',
            'name' => 'Esencial Boda',
            'description' => 'Comida y decoración para tu evento.',
            'estimated_price' => 25000,
        ]);
        $provider = Provider::query()->create([
            'name' => 'Banquetes El Buen Sabor',
            'category' => 'Comida',
            'contact_info' => '722 310 1001',
            'description' => 'Servicio de banquetes.',
        ]);
        $package->providers()->attach($provider->id, ['price' => 25000]);
        Package::query()->create([
            'category' => 'Boda',
            'name' => 'Fuera de presupuesto',
            'estimated_price' => 90000,
        ]);

        $this->actingAs($user, 'sanctum')
            ->getJson("/api/events/{$event->id}/packages")
            ->assertOk()
            ->assertJsonPath('available_budget', 80000)
            ->assertJsonCount(1, 'packages')
            ->assertJsonPath('packages.0.name', 'Esencial Boda')
            ->assertJsonPath('packages.0.category', 'Boda')
            ->assertJsonPath('packages.0.providers.0.name', 'Banquetes El Buen Sabor')
            ->assertJsonPath('packages.0.providers.0.category', 'Comida')
            ->assertJsonPath('packages.0.providers.0.pivot.price', 25000);
    }

    public function test_confirmation_persists_only_selected_providers_and_their_total(): void
    {
        $user = User::factory()->create();
        $venue = Venue::query()->create([
            'name' => 'Salón de Prueba',
            'address' => 'Toluca, Estado de México',
            'lat' => 19.2826,
            'lng' => -99.6557,
            'capacity' => 200,
            'min_price' => 20000,
            'max_price' => 60000,
        ]);
        $event = $this->createEvent($user, $venue, 'Boda', 100000);
        $packageTemplate = Package::query()->create([
            'category' => 'Boda',
            'name' => 'Paquete Selectivo',
            'estimated_price' => 20000,
        ]);
        $foodProvider = Provider::query()->create([
            'name' => 'Banquetes El Buen Sabor',
            'category' => 'Comida',
            'contact_info' => '722 310 1001',
            'description' => 'Servicio de banquetes.',
        ]);
        $decorProvider = Provider::query()->create([
            'name' => 'Floristería Magnolia',
            'category' => 'Decoración',
            'contact_info' => '722 310 1002',
            'description' => 'Arreglos florales.',
        ]);
        $packageTemplate->providers()->attach($foodProvider->id, ['price' => 12000]);
        $packageTemplate->providers()->attach($decorProvider->id, ['price' => 8000]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/events/{$event->id}/packages/{$packageTemplate->id}/confirm", [
                'provider_ids' => [$foodProvider->id],
            ]);

        $response->assertCreated()
            ->assertJsonPath('name', 'Paquete Selectivo')
            ->assertJsonPath('estimated_price', '12000.00')
            ->assertJsonCount(1, 'providers')
            ->assertJsonPath('providers.0.id', $foodProvider->id);

        $confirmedPackageId = $response->json('id');
        $this->assertDatabaseHas('packages', [
            'id' => $confirmedPackageId,
            'event_id' => $event->id,
            'estimated_price' => 12000,
            'category' => 'Boda',
        ]);
        $this->assertDatabaseHas('package_provider', [
            'package_id' => $confirmedPackageId,
            'provider_id' => $foodProvider->id,
            'price' => 12000,
        ]);
        $this->assertDatabaseMissing('package_provider', [
            'package_id' => $confirmedPackageId,
            'provider_id' => $decorProvider->id,
        ]);
    }

    public function test_package_endpoints_hide_events_owned_by_another_user(): void
    {
        $owner = User::factory()->create();
        $anotherUser = User::factory()->create();
        $event = $this->createEvent($owner, null, 'Boda', 50000);

        $this->actingAs($anotherUser, 'sanctum')
            ->getJson("/api/events/{$event->id}/packages")
            ->assertNotFound();
    }

    private function createEvent(User $user, ?Venue $venue, string $category, int $budget): Event
    {
        return $user->events()->create([
            'venue_id' => $venue?->id,
            'name' => 'Evento de Prueba',
            'category' => $category,
            'event_date' => '2027-05-10',
            'guest_count' => 50,
            'total_budget' => $budget,
            'public_uuid' => (string) Str::uuid(),
        ]);
    }
}
