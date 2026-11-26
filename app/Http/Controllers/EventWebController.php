<?php

/**
 * @empresa Bigbitbyte
 *
 * @autor Ivan Hernandez Hinojosa
 *
 * @creado 13/noviembre/2026
 *
 * @modificado 25/noviembre/2026
 *
 * @descripción Renderiza las páginas Inertia del panel de eventos y administra su plano de mesas.
 */

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Table;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class EventWebController extends Controller
{
    /**
     * Muestra los eventos del usuario autenticado.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        abort_unless($user instanceof User, 401);

        $events = $user->events()
            ->with(['venue', 'tables.guestCategory'])
            ->latest('event_date')
            ->latest('id')
            ->get();

        return Inertia::render('Events/Index', [
            'events' => $events,
        ]);
    }

    /**
     * Muestra los detalles de un evento que pertenece al usuario autenticado.
     */
    public function show(Request $request, int $eventId): Response
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $event = $user->events()
            ->with(['venue', 'guestCategories', 'tables.guestCategory', 'eventSpaces', 'packages.providers'])
            ->findOrFail($eventId);

        return Inertia::render('Events/Show', [
            'event' => $event,
        ]);
    }

    /**
     * Elimina un evento que pertenece al usuario autenticado.
     */
    public function destroy(Request $request, int $eventId): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $event = $user->events()->findOrFail($eventId);
        $event->delete();

        return to_route('web.events.index');
    }

    /**
     * Muestra el formulario para crear un evento.
     */
    public function create(): Response
    {
        return Inertia::render('Events/Create');
    }

    /**
     * Muestra las sugerencias de paquetes para un evento propio.
     */
    public function selectPackage(Request $request, int $eventId): Response
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $event = $user->events()->with('venue')->findOrFail($eventId);

        return Inertia::render('Events/SelectPackage', [
            'event' => $event,
        ]);
    }

    /**
     * Actualiza la posición de una mesa de un evento propio.
     */
    public function updateTablePosition(Request $request, int $eventId, int $tableId): JsonResponse
    {
        $validated = $request->validate([
            'position_x' => ['required', 'numeric', 'min:0', 'max:1000'],
            'position_y' => ['required', 'numeric', 'min:0', 'max:700'],
        ]);

        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $event = $user->events()->findOrFail($eventId);
        $table = DB::transaction(function () use ($event, $tableId, $validated): Table {
            $lockedEvent = Event::query()->whereKey($event->id)->lockForUpdate()->firstOrFail();
            $table = $lockedEvent->tables()->whereKey($tableId)->lockForUpdate()->firstOrFail();
            $table->update($validated);

            return $table;
        });

        return response()->json([
            'id' => $table->id,
            'position_x' => $table->position_x,
            'position_y' => $table->position_y,
        ]);
    }

    /**
     * Agrega una mesa al plano de un evento propio.
     */
    public function storeTable(Request $request, int $eventId): JsonResponse
    {
        $validated = $request->validate($this->tableRules());

        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $event = $user->events()->findOrFail($eventId);

        $table = DB::transaction(function () use ($event, $validated): Table {
            $lockedEvent = Event::query()->whereKey($event->id)->lockForUpdate()->firstOrFail();
            $tableNumber = (int) ($lockedEvent->tables()->max('table_number') ?? 0) + 1;

            return $lockedEvent->tables()->create([
                'table_number' => $tableNumber,
                'capacity' => $validated['capacity'] ?? 8,
                'shape' => $validated['shape'] ?? 'round',
                'width' => $validated['width'] ?? 76,
                'height' => $validated['height'] ?? 76,
                'position_x' => $validated['position_x'] ?? 0,
                'position_y' => $validated['position_y'] ?? 0,
            ]);
        });

        return response()->json($table, 201);
    }

    /**
     * Actualiza la forma, las dimensiones, el aforo o la posición de una mesa propia.
     */
    public function updateTable(Request $request, int $eventId, int $tableId): JsonResponse
    {
        $validated = $request->validate($this->tableRules());

        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $event = $user->events()->findOrFail($eventId);
        $table = DB::transaction(function () use ($event, $tableId, $validated): Table {
            $lockedEvent = Event::query()->whereKey($event->id)->lockForUpdate()->firstOrFail();
            $table = $lockedEvent->tables()->whereKey($tableId)->lockForUpdate()->firstOrFail();
            $table->update($validated);

            if (isset($validated['capacity'])) {
                $table->guests()
                    ->where('seat_number', '>', $validated['capacity'])
                    ->update(['table_id' => null, 'seat_number' => null]);
            }

            return $table;
        });

        return response()->json($table);
    }

    /**
     * Elimina una mesa del plano de un evento propio.
     */
    public function destroyTable(Request $request, int $eventId, int $tableId): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $event = $user->events()->findOrFail($eventId);
        DB::transaction(function () use ($event, $tableId): void {
            $lockedEvent = Event::query()->whereKey($event->id)->lockForUpdate()->firstOrFail();
            $table = $lockedEvent->tables()->whereKey($tableId)->lockForUpdate()->firstOrFail();
            $table->guests()->update(['table_id' => null, 'seat_number' => null]);
            $table->delete();
        });

        return response()->json(['id' => $tableId]);
    }

    /**
     * @return array<string, list<string>>
     */
    private function tableRules(): array
    {
        return [
            'capacity' => ['sometimes', 'integer', 'min:1', 'max:8'],
            'shape' => ['sometimes', 'string', 'in:round,square,rectangle'],
            'width' => ['sometimes', 'integer', 'min:40', 'max:200'],
            'height' => ['sometimes', 'integer', 'min:40', 'max:200'],
            'position_x' => ['sometimes', 'numeric', 'min:0', 'max:1000'],
            'position_y' => ['sometimes', 'numeric', 'min:0', 'max:700'],
        ];
    }
}
