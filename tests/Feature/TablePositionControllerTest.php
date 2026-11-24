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
 * @descripción Comprueba el guardado autenticado de posiciones de mesas.
 */

use App\Models\Event;
use App\Models\Table;
use App\Models\User;
use Illuminate\Support\Str;

test('saves new table coordinates for an event owned by the user', function (): void {
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
    $table = Table::query()->create([
        'event_id' => $event->id,
        'table_number' => 1,
        'capacity' => 8,
    ]);

    $this->actingAs($user)
        ->patchJson("/events/{$event->id}/tables/{$table->id}/position", [
            'position_x' => 125.5,
            'position_y' => 245.25,
        ])
        ->assertOk()
        ->assertJsonPath('id', $table->id)
        ->assertJsonPath('position_x', '125.50')
        ->assertJsonPath('position_y', '245.25');

    $this->assertDatabaseHas('tables', [
        'id' => $table->id,
        'position_x' => 125.5,
        'position_y' => 245.25,
    ]);
});

test('prevents a user from moving another users table', function (): void {
    $owner = User::factory()->create();
    $anotherUser = User::factory()->create();
    $event = Event::query()->create([
        'user_id' => $owner->id,
        'name' => 'Evento privado',
        'category' => 'Boda',
        'event_date' => '2027-05-10',
        'guest_count' => 8,
        'total_budget' => 25000,
        'public_uuid' => (string) Str::uuid(),
    ]);
    $table = Table::query()->create([
        'event_id' => $event->id,
        'table_number' => 1,
        'capacity' => 8,
    ]);

    $this->actingAs($anotherUser)
        ->patchJson("/events/{$event->id}/tables/{$table->id}/position", [
            'position_x' => 100,
            'position_y' => 200,
        ])
        ->assertNotFound();

    $this->assertDatabaseHas('tables', [
        'id' => $table->id,
        'position_x' => 0,
        'position_y' => 0,
    ]);
});
