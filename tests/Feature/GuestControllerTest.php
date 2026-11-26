<?php

/**
 * @empresa Bigbitbyte
 *
 * @autor Ivan Hernandez Hinojosa
 *
 * @creado 25/noviembre/2026
 *
 * @modificado 25/noviembre/2026
 *
 * @descripción Comprueba que el registro de invitados respeta cupos por categoría y por plan.
 */

use App\Models\Event;
use App\Models\Guest;
use App\Models\User;
use Illuminate\Support\Str;

function createGuestCapacityFixture(User $user, int $eventGuests, array $categories): Event
{
    $event = $user->events()->create([
        'name' => 'Evento con categorías',
        'category' => 'Boda',
        'event_date' => '2027-05-10',
        'guest_count' => $eventGuests,
        'total_budget' => 25000,
        'public_uuid' => (string) Str::uuid(),
    ]);

    foreach ($categories as $priority => $category) {
        $event->guestCategories()->create([
            'name' => $category['name'],
            'guest_count' => $category['guest_count'],
            'priority' => $priority + 1,
        ]);
    }

    return $event;
}

test('rejects a guest when the selected category is already full', function (): void {
    $user = User::factory()->create();
    $event = createGuestCapacityFixture($user, 2, [
        ['name' => 'Familia', 'guest_count' => 1],
        ['name' => 'Amigos', 'guest_count' => 1],
    ]);
    $category = $event->guestCategories()->firstWhere('name', 'Familia');
    $event->guests()->create([
        'name' => 'Ana Pérez',
        'category_id' => $category->id,
        'qr_token' => (string) Str::uuid(),
    ]);

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/events/{$event->id}/guests", [
            'name' => 'Luis García',
            'category_id' => $category->id,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('category_id');

    $this->assertSame(1, Guest::query()->where('category_id', $category->id)->count());
});

test('allows a guest in another category with remaining capacity', function (): void {
    $user = User::factory()->create();
    $event = createGuestCapacityFixture($user, 2, [
        ['name' => 'Familia', 'guest_count' => 1],
        ['name' => 'Amigos', 'guest_count' => 1],
    ]);
    $family = $event->guestCategories()->firstWhere('name', 'Familia');
    $friends = $event->guestCategories()->firstWhere('name', 'Amigos');
    $event->guests()->create([
        'name' => 'Ana Pérez',
        'category_id' => $family->id,
        'qr_token' => (string) Str::uuid(),
    ]);

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/events/{$event->id}/guests", [
            'name' => 'Luis García',
            'category_id' => $friends->id,
        ])
        ->assertCreated()
        ->assertJsonPath('guest.category_id', $friends->id);

    $this->assertSame(2, $event->guests()->count());
});

test('rejects a guest when the event has reached its total guest count', function (): void {
    $user = User::factory()->create();
    $event = createGuestCapacityFixture($user, 1, [
        ['name' => 'Familia', 'guest_count' => 1],
        ['name' => 'Amigos', 'guest_count' => 1],
    ]);
    $family = $event->guestCategories()->firstWhere('name', 'Familia');
    $friends = $event->guestCategories()->firstWhere('name', 'Amigos');
    $event->guests()->create([
        'name' => 'Ana Pérez',
        'category_id' => $family->id,
        'qr_token' => (string) Str::uuid(),
    ]);

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/events/{$event->id}/guests", [
            'name' => 'Luis García',
            'category_id' => $friends->id,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('guest_count');

    $this->assertSame(1, $event->guests()->count());
});

test('rejects a guest that exceeds the plan guest limit', function (): void {
    $user = User::factory()->create();
    $event = createGuestCapacityFixture($user, 60, [
        ['name' => 'General', 'guest_count' => 60],
    ]);
    $category = $event->guestCategories()->first();

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/events/{$event->id}/guests", [
            'name' => 'Invitado sin plan',
            'category_id' => $category->id,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('guest_count');

    $this->assertSame(0, $event->guests()->count());
});
