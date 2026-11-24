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
 * @descripción Busca salones por aforo, rango de precio y nombre.
 */

namespace App\Http\Controllers;

use App\Models\Venue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VenueController extends Controller
{
    /**
     * Busca salones que coinciden con los filtros opcionales recibidos.
     */
    public function search(Request $request): JsonResponse
    {
        $maxPriceRules = ['sometimes', 'numeric', 'min:0'];

        if ($request->filled('min_price')) {
            $maxPriceRules[] = 'gte:min_price';
        }

        $filters = $request->validate([
            'capacity' => ['sometimes', 'integer', 'min:1'],
            'min_price' => ['sometimes', 'numeric', 'min:0'],
            'max_price' => $maxPriceRules,
            'name' => ['sometimes', 'string', 'max:255'],
        ]);

        $venues = Venue::query()
            ->when(isset($filters['capacity']), function (Builder $query) use ($filters): void {
                $query->where('capacity', '>=', $filters['capacity']);
            })
            ->when(isset($filters['min_price']), function (Builder $query) use ($filters): void {
                $query->where('max_price', '>=', $filters['min_price']);
            })
            ->when(isset($filters['max_price']), function (Builder $query) use ($filters): void {
                $query->where('min_price', '<=', $filters['max_price']);
            })
            ->when(isset($filters['name']), function (Builder $query) use ($filters): void {
                $searchTerm = '%'.$filters['name'].'%';

                $query->where(function (Builder $venueQuery) use ($searchTerm): void {
                    $venueQuery->where('name', 'like', $searchTerm)
                        ->orWhere('address', 'like', $searchTerm);
                });
            })
            ->orderBy('name')
            ->get();

        return response()->json($venues);
    }
}
