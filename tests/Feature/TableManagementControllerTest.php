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
 * @descripción Comprueba el alta, edición y baja de mesas del plano de un evento.
 */

use App\Models\Event;
use App\Models\Guest;
use App\Models\User;
use Illuminate\Support\Str;

test('creates a table with default values for an event owned by the user', function (): void {
    $user = User::factory()->create();
    $event = Event::query()->create([
        'user_id' => $user->id,
        'name' => 'Evento con plano',
        'category' => 'Boda',
        'event_date' => '2027-05-10',
        'guest_count' => 8,
        'total_budget' => 25000,
        'public_uuid' => (string) Str::uuid(),
    ]);

    $this->actingAs($user)
        ->postJson("/events/{$event->id}/tables", [])
        ->assertCreated()
        ->assertJsonPath('table_number', 1)
        ->assertJsonPath('capacity', 8)
        ->assertJsonPath('shape', 'round')
        ->assertJsonPath('width', 76)
        ->assertJsonPath('height', 76);

    $this->assertDatabaseHas('tables', [
        'event_id' => $event->id,
        'table_number' => 1,
        'capacity' => 8,
        'shape' => 'round',
        'width' => 76,
        'height' => 76,
    ]);
});

test('creates a table with the requested shape dimensions capacity and position', function (): void {
    $user = User::factory()->create();
    $event = $user->events()->create([
        'name' => 'Evento con plano',
        'category' => 'Boda',
        'event_date' => '2027-05-10',
        'guest_count' => 8,
        'total_budget' => 25000,
        'public_uuid' => (string) Str::uuid(),
    ]);

    $this->actingAs($user)
        ->postJson("/events/{$event->id}/tables", [
            'capacity' => 6,
            'shape' => 'rectangle',
            'width' => 120,
            'height' => 80,
            'position_x' => 200,
            'position_y' => 300,
        ])
        ->assertCreated()
        ->assertJsonPath('shape', 'rectangle')
        ->assertJsonPath('capacity', 6)
        ->assertJsonPath('width', 120)
        ->assertJsonPath('height', 80);

    $this->assertDatabaseHas('tables', [
        'event_id' => $event->id,
        'table_number' => 1,
        'shape' => 'rectangle',
        'width' => 120,
        'height' => 80,
        'capacity' => 6,
        'position_x' => 200,
        'position_y' => 300,
    ]);
});

test('assigns the next table number after an existing one', function (): void {
    $user = User::factory()->create();
    $event = $user->events()->create([
        'name' => 'Evento con plano',
        'category' => 'Boda',
        'event_date' => '2027-05-10',
        'guest_count' => 16,
        'total_budget' => 25000,
        'public_uuid' => (string) Str::uuid(),
    ]);
    $event->tables()->create([
        'table_number' => 4,
        'capacity' => 8,
    ]);

    $this->actingAs($user)
        ->postJson("/events/{$event->id}/tables", [])
        ->assertCreated()
        ->assertJsonPath('table_number', 5);

    $this->assertDatabaseCount('tables', 2);
});

test('rejects invalid table values', function (array $payload, string $field): void {
    $user = User::factory()->create();
    $event = $user->events()->create([
        'name' => 'Evento con plano',
        'category' => 'Boda',
        'event_date' => '2027-05-10',
        'guest_count' => 8,
        'total_budget' => 25000,
        'public_uuid' => (string) Str::uuid(),
    ]);

    $this->actingAs($user)
        ->postJson("/events/{$event->id}/tables", $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors($field);

    $this->assertDatabaseCount('tables', 0);
})->with([
    'capacity above eight' => [['capacity' => 9], 'capacity'],
    'unknown shape' => [['shape' => 'triangle'], 'shape'],
    'width too small' => [['width' => 10], 'width'],
    'height too large' => [['height' => 300], 'height'],
]);

test('updates the shape dimensions capacity and position of an owned table', function (): void {
    $user = User::factory()->create();
    $event = $user->events()->create([
        'name' => 'Evento con plano',
        'category' => 'Boda',
        'event_date' => '2027-05-10',
        'guest_count' => 8,
        'total_budget' => 25000,
        'public_uuid' => (string) Str::uuid(),
    ]);
    $table = $event->tables()->create([
        'table_number' => 1,
        'capacity' => 8,
    ]);

    $this->actingAs($user)
        ->patchJson("/events/{$event->id}/tables/{$table->id}", [
            'shape' => 'square',
            'width' => 100,
            'height' => 100,
            'capacity' => 4,
            'position_x' => 150,
            'position_y' => 250,
        ])
        ->assertOk()
        ->assertJsonPath('shape', 'square')
        ->assertJsonPath('width', 100)
        ->assertJsonPath('height', 100)
        ->assertJsonPath('capacity', 4);

    $this->assertDatabaseHas('tables', [
        'id' => $table->id,
        'shape' => 'square',
        'width' => 100,
        'height' => 100,
        'capacity' => 4,
        'position_x' => 150,
        'position_y' => 250,
    ]);
});

test('deletes only the requested table of an owned event', function (): void {
    $user = User::factory()->create();
    $event = $user->events()->create([
        'name' => 'Evento con plano',
        'category' => 'Boda',
        'event_date' => '2027-05-10',
        'guest_count' => 16,
        'total_budget' => 25000,
        'public_uuid' => (string) Str::uuid(),
    ]);
    $table = $event->tables()->create([
        'table_number' => 1,
        'capacity' => 8,
    ]);
    $event->tables()->create([
        'table_number' => 2,
        'capacity' => 8,
    ]);

    $this->actingAs($user)
        ->deleteJson("/events/{$event->id}/tables/{$table->id}")
        ->assertOk()
        ->assertJsonPath('id', $table->id);

    $this->assertDatabaseMissing('tables', ['id' => $table->id]);
    $this->assertDatabaseCount('tables', 1);
});

test('prevents a user from adding a table to another users event', function (): void {
    $owner = User::factory()->create();
    $anotherUser = User::factory()->create();
    $event = $owner->events()->create([
        'name' => 'Evento privado',
        'category' => 'Boda',
        'event_date' => '2027-05-10',
        'guest_count' => 8,
        'total_budget' => 25000,
        'public_uuid' => (string) Str::uuid(),
    ]);

    $this->actingAs($anotherUser)
        ->postJson("/events/{$event->id}/tables", [])
        ->assertNotFound();

    $this->assertDatabaseCount('tables', 0);
});

test('prevents a user from updating another users table', function (): void {
    $owner = User::factory()->create();
    $anotherUser = User::factory()->create();
    $event = $owner->events()->create([
        'name' => 'Evento privado',
        'category' => 'Boda',
        'event_date' => '2027-05-10',
        'guest_count' => 8,
        'total_budget' => 25000,
        'public_uuid' => (string) Str::uuid(),
    ]);
    $table = $event->tables()->create([
        'table_number' => 1,
        'capacity' => 8,
    ]);

    $this->actingAs($anotherUser)
        ->patchJson("/events/{$event->id}/tables/{$table->id}", [
            'shape' => 'rectangle',
            'width' => 150,
        ])
        ->assertNotFound();

    $this->assertDatabaseHas('tables', [
        'id' => $table->id,
        'shape' => 'round',
        'width' => 76,
    ]);
});

test('prevents a user from deleting another users table', function (): void {
    $owner = User::factory()->create();
    $anotherUser = User::factory()->create();
    $event = $owner->events()->create([
        'name' => 'Evento privado',
        'category' => 'Boda',
        'event_date' => '2027-05-10',
        'guest_count' => 8,
        'total_budget' => 25000,
        'public_uuid' => (string) Str::uuid(),
    ]);
    $table = $event->tables()->create([
        'table_number' => 1,
        'capacity' => 8,
    ]);

    $this->actingAs($anotherUser)
        ->deleteJson("/events/{$event->id}/tables/{$table->id}")
        ->assertNotFound();

    $this->assertDatabaseHas('tables', ['id' => $table->id]);
});

function createGuestForTableCleanup(Event $event, string $name): Guest
{
    return $event->guests()->create([
        'name' => $name,
        'email' => null,
        'category_id' => $event->guestCategories()->create([
            'name' => 'Familia',
            'guest_count' => 2,
            'priority' => 1,
        ])->id,
        'qr_token' => (string) Str::uuid(),
    ]);
}

test('releases seats beyond the new capacity when a table is reduced', function (): void {
    $user = User::factory()->create();
    $event = $user->events()->create([
        'name' => 'Evento con plano',
        'category' => 'Boda',
        'event_date' => '2027-05-10',
        'guest_count' => 8,
        'total_budget' => 25000,
        'public_uuid' => (string) Str::uuid(),
    ]);
    $table = $event->tables()->create([
        'table_number' => 1,
        'capacity' => 8,
    ]);
    $keptGuest = createGuestForTableCleanup($event, 'Ana Pérez');
    $keptGuest->update(['table_id' => $table->id, 'seat_number' => 2]);
    $releasedGuest = createGuestForTableCleanup($event, 'Luis García');
    $releasedGuest->update(['table_id' => $table->id, 'seat_number' => 6]);

    $this->actingAs($user)
        ->patchJson("/events/{$event->id}/tables/{$table->id}", ['capacity' => 4])
        ->assertOk()
        ->assertJsonPath('capacity', 4);

    $this->assertDatabaseHas('guests', [
        'id' => $keptGuest->id,
        'table_id' => $table->id,
        'seat_number' => 2,
    ]);
    $this->assertDatabaseHas('guests', [
        'id' => $releasedGuest->id,
        'table_id' => null,
        'seat_number' => null,
    ]);
});

test('releases all seats when a table is deleted', function (): void {
    $user = User::factory()->create();
    $event = $user->events()->create([
        'name' => 'Evento con plano',
        'category' => 'Boda',
        'event_date' => '2027-05-10',
        'guest_count' => 8,
        'total_budget' => 25000,
        'public_uuid' => (string) Str::uuid(),
    ]);
    $table = $event->tables()->create([
        'table_number' => 1,
        'capacity' => 8,
    ]);
    $guest = createGuestForTableCleanup($event, 'Marta López');
    $guest->update(['table_id' => $table->id, 'seat_number' => 3]);

    $this->actingAs($user)
        ->deleteJson("/events/{$event->id}/tables/{$table->id}")
        ->assertOk();

    $this->assertDatabaseMissing('tables', ['id' => $table->id]);
    $this->assertDatabaseHas('guests', [
        'id' => $guest->id,
        'table_id' => null,
        'seat_number' => null,
    ]);
});
