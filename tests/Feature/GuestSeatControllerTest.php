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
 * @descripción Comprueba la asignación manual de sillas a los invitados de un evento.
 */

use App\Models\Event;
use App\Models\Guest;
use App\Models\User;
use Illuminate\Support\Str;

function createSeatReservationFixture(User $user, int $capacity = 4): array
{
    $event = $user->events()->create([
        'name' => 'Evento con sillas',
        'category' => 'Boda',
        'event_date' => '2027-05-10',
        'guest_count' => 8,
        'total_budget' => 25000,
        'public_uuid' => (string) Str::uuid(),
    ]);
    $table = $event->tables()->create([
        'table_number' => 1,
        'capacity' => $capacity,
    ]);
    $category = $event->guestCategories()->create([
        'name' => 'Familia',
        'guest_count' => 8,
        'priority' => 1,
    ]);

    return [$event, $table, $category];
}

function createGuestForSeat(Event $event, string $name): Guest
{
    return $event->guests()->create([
        'name' => $name,
        'email' => null,
        'category_id' => $event->guestCategories()->first()->id,
        'qr_token' => (string) Str::uuid(),
    ]);
}

test('assigns a seat to a guest of an owned event', function (): void {
    $user = User::factory()->create();
    [$event, $table] = createSeatReservationFixture($user);
    $guest = createGuestForSeat($event, 'Ana Pérez');

    $this->actingAs($user, 'sanctum')
        ->patchJson("/api/events/{$event->id}/guests/{$guest->id}/seat", [
            'table_id' => $table->id,
            'seat_number' => 3,
        ])
        ->assertOk()
        ->assertJsonPath('guest.table_id', $table->id)
        ->assertJsonPath('guest.seat_number', 3);

    $this->assertDatabaseHas('guests', [
        'id' => $guest->id,
        'table_id' => $table->id,
        'seat_number' => 3,
    ]);
});

test('releases the assigned seat when both values are null', function (): void {
    $user = User::factory()->create();
    [$event, $table] = createSeatReservationFixture($user);
    $guest = createGuestForSeat($event, 'Luis García');
    $guest->update(['table_id' => $table->id, 'seat_number' => 2]);

    $this->actingAs($user, 'sanctum')
        ->patchJson("/api/events/{$event->id}/guests/{$guest->id}/seat", [
            'table_id' => null,
            'seat_number' => null,
        ])
        ->assertOk()
        ->assertJsonPath('guest.table_id', null)
        ->assertJsonPath('guest.seat_number', null);

    $this->assertDatabaseHas('guests', [
        'id' => $guest->id,
        'table_id' => null,
        'seat_number' => null,
    ]);
});

test('rejects a seat above the table capacity', function (): void {
    $user = User::factory()->create();
    [$event, $table] = createSeatReservationFixture($user, 4);
    $guest = createGuestForSeat($event, 'Marta López');

    $this->actingAs($user, 'sanctum')
        ->patchJson("/api/events/{$event->id}/guests/{$guest->id}/seat", [
            'table_id' => $table->id,
            'seat_number' => 5,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('seat_number');

    $this->assertDatabaseHas('guests', [
        'id' => $guest->id,
        'table_id' => null,
        'seat_number' => null,
    ]);
});

test('moves an occupied seat to the guest that requests it', function (): void {
    $user = User::factory()->create();
    [$event, $table] = createSeatReservationFixture($user);
    $firstGuest = createGuestForSeat($event, 'Ana Pérez');
    $secondGuest = createGuestForSeat($event, 'Luis García');
    $firstGuest->update(['table_id' => $table->id, 'seat_number' => 2]);

    $this->actingAs($user, 'sanctum')
        ->patchJson("/api/events/{$event->id}/guests/{$secondGuest->id}/seat", [
            'table_id' => $table->id,
            'seat_number' => 2,
        ])
        ->assertOk()
        ->assertJsonPath('guest.id', $secondGuest->id)
        ->assertJsonPath('guest.seat_number', 2);

    $this->assertDatabaseHas('guests', [
        'id' => $firstGuest->id,
        'table_id' => null,
        'seat_number' => null,
    ]);
    $this->assertDatabaseHas('guests', [
        'id' => $secondGuest->id,
        'table_id' => $table->id,
        'seat_number' => 2,
    ]);
});

test('rejects an incomplete seat assignment', function (): void {
    $user = User::factory()->create();
    [$event, $table] = createSeatReservationFixture($user);
    $guest = createGuestForSeat($event, 'Sofía Ruiz');

    $this->actingAs($user, 'sanctum')
        ->patchJson("/api/events/{$event->id}/guests/{$guest->id}/seat", [
            'table_id' => $table->id,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('seat_number');
});

test('prevents a user from assigning a seat in another users event', function (): void {
    $owner = User::factory()->create();
    $anotherUser = User::factory()->create();
    [$event, $table] = createSeatReservationFixture($owner);
    $guest = createGuestForSeat($event, 'Invitado privado');

    $this->actingAs($anotherUser, 'sanctum')
        ->patchJson("/api/events/{$event->id}/guests/{$guest->id}/seat", [
            'table_id' => $table->id,
            'seat_number' => 1,
        ])
        ->assertNotFound();

    $this->assertDatabaseHas('guests', [
        'id' => $guest->id,
        'table_id' => null,
        'seat_number' => null,
    ]);
});

test('prevents assigning a table from another event', function (): void {
    $user = User::factory()->create();
    [$event, $table] = createSeatReservationFixture($user);
    $anotherEvent = $user->events()->create([
        'name' => 'Otro evento',
        'category' => 'Boda',
        'event_date' => '2027-06-10',
        'guest_count' => 8,
        'total_budget' => 25000,
        'public_uuid' => (string) Str::uuid(),
    ]);
    $anotherTable = $anotherEvent->tables()->create([
        'table_number' => 1,
        'capacity' => 8,
    ]);
    $guest = createGuestForSeat($event, 'Invitado propio');

    $this->actingAs($user, 'sanctum')
        ->patchJson("/api/events/{$event->id}/guests/{$guest->id}/seat", [
            'table_id' => $anotherTable->id,
            'seat_number' => 1,
        ])
        ->assertNotFound();

    $this->assertDatabaseHas('guests', [
        'id' => $guest->id,
        'table_id' => null,
        'seat_number' => null,
    ]);
});
