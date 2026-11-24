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
 * @descripción Verifica la distribución ordenada de invitados en mesas.
 */

use App\Models\User;
use App\Services\TableDistributionService;
use Illuminate\Support\Str;

test('creates tables of eight and preserves category priority order', function (): void {
    $user = User::factory()->create();
    $event = $user->events()->create([
        'name' => 'Evento de Prueba',
        'category' => 'Boda',
        'event_date' => '2027-05-10',
        'guest_count' => 30,
        'total_budget' => 50000,
        'public_uuid' => (string) Str::uuid(),
    ]);

    $secondCategory = $event->guestCategories()->create([
        'name' => 'Categoría 2',
        'guest_count' => 10,
        'priority' => 2,
    ]);
    $firstCategory = $event->guestCategories()->create([
        'name' => 'Categoría 1',
        'guest_count' => 20,
        'priority' => 1,
    ]);

    $tables = app(TableDistributionService::class)->distribute($event);

    expect($tables->where('guest_category_id', $firstCategory->id)->pluck('capacity')->all())
        ->toBe([8, 8, 4]);
    expect($tables->where('guest_category_id', $secondCategory->id)->pluck('capacity')->all())
        ->toBe([8, 2]);
    expect($tables->where('guest_category_id', $firstCategory->id)->pluck('table_number')->all())
        ->toBe([1, 2, 3]);
    expect($tables->where('guest_category_id', $secondCategory->id)->pluck('table_number')->all())
        ->toBe([4, 5]);

    $this->assertDatabaseCount('tables', 5);
});

test('replaces prior tables when a distribution is recalculated', function (): void {
    $user = User::factory()->create();
    $event = $user->events()->create([
        'name' => 'Evento de Prueba',
        'category' => 'Boda',
        'event_date' => '2027-05-10',
        'guest_count' => 10,
        'total_budget' => 50000,
        'public_uuid' => (string) Str::uuid(),
    ]);
    $event->guestCategories()->create([
        'name' => 'Categoría 1',
        'guest_count' => 10,
        'priority' => 1,
    ]);

    $service = app(TableDistributionService::class);
    $service->distribute($event);
    $tables = $service->distribute($event);

    expect($tables->pluck('capacity')->all())->toBe([8, 2]);
    $this->assertDatabaseCount('tables', 2);
});
