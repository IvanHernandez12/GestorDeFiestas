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
 * @descripción Calcula presupuesto para proveedores y sugiere paquetes compatibles.
 */

namespace App\Services;

use App\Models\Event;
use App\Models\Package;
use Illuminate\Database\Eloquent\Collection;

class BudgetService
{
    /**
     * Resta el precio mínimo del salón al presupuesto y limita el resultado a cero.
     */
    public function calculateAvailableBudget(Event $event): float
    {
        $event->loadMissing('venue');

        $totalBudgetInCents = (int) round((float) $event->total_budget * 100);
        $venueMinimumInCents = (int) round((float) ($event->venue?->min_price ?? 0) * 100);
        $paidPackagesInCents = (int) round(
            (float) $event->packages()->where('is_paid', true)->sum('estimated_price') * 100
        );

        return max(0, $totalBudgetInCents - $venueMinimumInCents - $paidPackagesInCents) / 100;
    }

    /**
     * Busca paquetes de la misma categoría que quepan en el presupuesto disponible.
     *
     * @return Collection<int, Package>
     */
    public function suggestPackage(Event $event): Collection
    {
        $availableBudget = $this->calculateAvailableBudget($event);

        return Package::query()
            ->with('providers')
            ->where('category', $event->category)
            ->where('estimated_price', '<=', $availableBudget)
            ->orderBy('estimated_price')
            ->orderBy('id')
            ->get();
    }
}
