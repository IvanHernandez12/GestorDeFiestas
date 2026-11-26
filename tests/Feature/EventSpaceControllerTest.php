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
 * @descripción Comprueba el alta, edición y baja de espacios del plano de un evento.
 */

use App\Models\User;
use Illuminate\Support\Str;

function createEventForSpaces(User $user)
{
    return $user->events()->create([
        'name' => 'Evento con espacios',
        'category' => 'Boda',
        'event_date' => '2027-05-10',
        'guest_count' => 80,
        'total_budget' => 80000,
        'public_uuid' => (string) Str::uuid(),
    ]);
}

test('creates a space with default dimensions for an owned event', function (): void {
    $user = User::factory()->create();
    $event = createEventForSpaces($user);

    $this->actingAs($user)
        ->postJson("/events/{$event->id}/spaces", [
            'name' => 'Barra principal',
            'type' => 'bar',
        ])
        ->assertCreated()
        ->assertJsonPath('name', 'Barra principal')
        ->assertJsonPath('type', 'bar')
        ->assertJsonPath('width', 160)
        ->assertJsonPath('height', 100)
        ->assertJsonPath('position_x', 0)
        ->assertJsonPath('position_y', 0);

    $this->assertDatabaseHas('event_spaces', [
        'event_id' => $event->id,
        'name' => 'Barra principal',
        'type' => 'bar',
        'width' => 160,
        'height' => 100,
    ]);
});

test('creates a preset space with custom dimensions and position', function (): void {
    $user = User::factory()->create();
    $event = createEventForSpaces($user);

    $this->actingAs($user)
        ->postJson("/events/{$event->id}/spaces", [
            'name' => 'Pista central',
            'type' => 'dance_floor',
            'width' => 300,
            'height' => 200,
            'position_x' => 120,
            'position_y' => 240,
        ])
        ->assertCreated()
        ->assertJsonPath('type', 'dance_floor')
        ->assertJsonPath('width', 300)
        ->assertJsonPath('height', 200);

    $this->assertDatabaseHas('event_spaces', [
        'event_id' => $event->id,
        'type' => 'dance_floor',
        'position_x' => 120,
        'position_y' => 240,
    ]);
});

test('rejects invalid space values', function (array $payload, string $field): void {
    $user = User::factory()->create();
    $event = createEventForSpaces($user);

    $this->actingAs($user)
        ->postJson("/events/{$event->id}/spaces", $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors($field);

    $this->assertDatabaseCount('event_spaces', 0);
})->with([
    'missing name' => [['type' => 'bar'], 'name'],
    'unknown type' => [['name' => 'Zona', 'type' => 'pool'], 'type'],
    'width too large' => [['name' => 'Zona', 'type' => 'other', 'width' => 700], 'width'],
    'negative position' => [['name' => 'Zona', 'type' => 'other', 'position_x' => -1], 'position_x'],
]);

test('updates and deletes an owned space', function (): void {
    $user = User::factory()->create();
    $event = createEventForSpaces($user);
    $space = $event->eventSpaces()->create([
        'name' => 'Barra',
        'type' => 'bar',
        'width' => 180,
        'height' => 90,
    ]);

    $this->actingAs($user)
        ->patchJson("/events/{$event->id}/spaces/{$space->id}", [
            'name' => 'Mesa de regalos',
            'type' => 'gift_table',
            'width' => 150,
            'position_y' => 320,
        ])
        ->assertOk()
        ->assertJsonPath('name', 'Mesa de regalos')
        ->assertJsonPath('type', 'gift_table')
        ->assertJsonPath('width', 150);

    $this->assertDatabaseHas('event_spaces', [
        'id' => $space->id,
        'name' => 'Mesa de regalos',
        'type' => 'gift_table',
        'position_y' => 320,
    ]);

    $this->actingAs($user)
        ->deleteJson("/events/{$event->id}/spaces/{$space->id}")
        ->assertOk()
        ->assertJsonPath('id', $space->id);

    $this->assertDatabaseMissing('event_spaces', ['id' => $space->id]);
});

test('prevents a user from managing spaces of another users event', function (): void {
    $owner = User::factory()->create();
    $anotherUser = User::factory()->create();
    $event = createEventForSpaces($owner);
    $space = $event->eventSpaces()->create([
        'name' => 'Barra privada',
        'type' => 'bar',
    ]);

    $this->actingAs($anotherUser)
        ->postJson("/events/{$event->id}/spaces", ['name' => 'Intruso', 'type' => 'bar'])
        ->assertNotFound();

    $this->actingAs($anotherUser)
        ->patchJson("/events/{$event->id}/spaces/{$space->id}", ['name' => 'Modificado'])
        ->assertNotFound();

    $this->actingAs($anotherUser)
        ->deleteJson("/events/{$event->id}/spaces/{$space->id}")
        ->assertNotFound();

    $this->assertDatabaseCount('event_spaces', 1);
    $this->assertDatabaseHas('event_spaces', ['id' => $space->id, 'name' => 'Barra privada']);
});
