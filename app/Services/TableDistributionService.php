<?php

/**
 * @empresa Bigbitbyte
 *
 * @autor Ivan Hernandez Hinojosa
 *
 * @creado 13/noviembre/2026
 *
 * @modificado 26/noviembre/2026
 *
 * @descripción Distribuye invitados por categoría en mesas de hasta ocho lugares.
 */

namespace App\Services;

use App\Models\Event;
use App\Models\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class TableDistributionService
{
    /**
     * Crea mesas según la cantidad de invitados y el orden de prioridad de categorías.
     *
     * @return Collection<int, Table>
     */
    public function distribute(Event $event): Collection
    {
        return DB::transaction(function () use ($event): Collection {
            $event = Event::query()->whereKey($event->id)->lockForUpdate()->firstOrFail();
            $event->guests()->update(['table_id' => null, 'seat_number' => null]);
            $event->tables()->delete();

            $tables = new Collection;
            $tableNumber = 1;
            $categories = $event->guestCategories()
                ->orderBy('priority')
                ->orderBy('id')
                ->get();
            $assignedGuestCount = (int) $categories->sum('guest_count');

            if ($assignedGuestCount > $event->guest_count) {
                throw new InvalidArgumentException('La suma de invitados por categoría no puede exceder el total del evento.');
            }

            $remainingGuestCount = $event->guest_count - $assignedGuestCount;

            if ($remainingGuestCount > 0) {
                $generalCategory = $event->guestCategories()->firstOrNew(['name' => 'Categoría General']);

                if (! $generalCategory->public_uuid) {
                    $generalCategory->public_uuid = (string) Str::uuid();
                }

                $generalCategory->fill([
                    'guest_count' => $remainingGuestCount,
                    'priority' => ((int) $categories->max('priority')) + 1,
                ]);
                $generalCategory->save();
                $categories->push($generalCategory);
            }

            foreach ($categories as $category) {
                $remainingGuests = $category->guest_count;

                while ($remainingGuests > 0) {
                    $tableCapacity = min(8, $remainingGuests);
                    $tables->push($event->tables()->create([
                        'guest_category_id' => $category->id,
                        'table_number' => $tableNumber,
                        'capacity' => $tableCapacity,
                    ]));

                    $remainingGuests -= $tableCapacity;
                    $tableNumber++;
                }
            }

            return $tables;
        });
    }
}
