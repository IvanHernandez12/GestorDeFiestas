<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PaymentSimulationControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_confirming_an_owned_package_marks_it_as_paid_and_redirects_to_events(): void
    {
        config()->set('services.payments.simulation', true);
        $user = User::factory()->create();
        $event = $user->events()->create([
            'name' => 'Boda de prueba',
            'category' => 'Boda',
            'event_date' => '2027-05-10',
            'guest_count' => 20,
            'total_budget' => 40000,
            'public_uuid' => (string) Str::uuid(),
        ]);
        $package = $event->packages()->create([
            'category' => 'Boda',
            'name' => 'Paquete Premium',
            'estimated_price' => 85000,
            'is_paid' => false,
            'payment_reference' => (string) Str::uuid(),
        ]);

        $response = $this->actingAs($user)
            ->post('/simulate-payment/confirm', ['type' => 'package', 'id' => $package->id]);

        $response->assertRedirect('/events');
        $this->assertDatabaseHas('packages', ['id' => $package->id, 'is_paid' => true]);
    }

    public function test_confirmation_returns_404_for_another_users_package_and_leaves_it_unpaid(): void
    {
        config()->set('services.payments.simulation', true);
        $owner = User::factory()->create();
        $anotherUser = User::factory()->create();
        $event = $owner->events()->create([
            'name' => 'Evento privado',
            'category' => 'Boda',
            'event_date' => '2027-05-10',
            'guest_count' => 20,
            'total_budget' => 40000,
            'public_uuid' => (string) Str::uuid(),
        ]);
        $package = $event->packages()->create([
            'category' => 'Boda',
            'name' => 'Paquete privado',
            'estimated_price' => 85000,
            'is_paid' => false,
            'payment_reference' => (string) Str::uuid(),
        ]);

        $this->actingAs($anotherUser)
            ->post('/simulate-payment/confirm', ['type' => 'package', 'id' => $package->id])
            ->assertNotFound();

        $this->assertDatabaseHas('packages', ['id' => $package->id, 'is_paid' => false]);
    }

    public function test_confirming_a_paid_plan_updates_the_authenticated_users_plan(): void
    {
        config()->set('services.payments.simulation', true);
        $user = User::factory()->create();
        $plan = Plan::query()->create([
            'name' => 'Básico',
            'slug' => 'basic',
            'price' => 299,
            'max_events' => 5,
            'max_guests' => 200,
            'is_free' => false,
        ]);

        $response = $this->actingAs($user)
            ->post('/simulate-payment/confirm', ['type' => 'plan', 'id' => $plan->id]);

        $response->assertRedirect('/events');
        $this->assertDatabaseHas('users', ['id' => $user->id, 'plan_id' => $plan->id]);
    }
}
