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
 * @descripción Renderiza las páginas Inertia del panel de eventos.
 */

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
            ->with(['venue', 'guestCategories', 'tables.guestCategory', 'packages.providers'])
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
        $table = $event->tables()->findOrFail($tableId);
        $table->update($validated);

        return response()->json([
            'id' => $table->id,
            'position_x' => $table->position_x,
            'position_y' => $table->position_y,
        ]);
    }
}
