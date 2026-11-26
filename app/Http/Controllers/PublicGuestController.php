<?php

/**
 * @empresa Bigbitbyte
 *
 * @autor Ivan Hernandez Hinojosa
 *
 * @creado 20/noviembre/2026
 *
 * @modificado 26/noviembre/2026
 *
 * @descripción Expone invitaciones individuales y grupales, el plano y el salón mediante tokens públicos.
 */

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Guest;
use App\Models\GuestCategory;
use App\Models\Venue;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PublicGuestController extends Controller
{
    /**
     * Devuelve los datos del invitado, su evento y el salón mediante su token QR.
     */
    public function show(string $qrToken): JsonResponse
    {
        $guest = $this->findGuestByToken($qrToken);

        if (! $guest instanceof Guest) {
            return response()->json([
                'message' => 'Invitación no encontrada',
            ], 404);
        }

        $event = $guest->event;

        return response()->json([
            'guest' => [
                'id' => $guest->id,
                'name' => $guest->name,
                'category' => $guest->category?->name,
                'invitation_status' => $guest->invitation_status,
                'invitation_status_label' => Guest::statusLabel($guest->invitation_status),
            ],
            'reservation' => $guest->table === null ? null : [
                'table_number' => (int) $guest->table->table_number,
                'seat_number' => $guest->seat_number,
                'table' => [
                    'position_x' => (float) $guest->table->position_x,
                    'position_y' => (float) $guest->table->position_y,
                    'shape' => $guest->table->shape,
                    'width' => (int) $guest->table->width,
                    'height' => (int) $guest->table->height,
                    'capacity' => (int) $guest->table->capacity,
                ],
            ],
            'event' => [
                'id' => $event->id,
                'public_uuid' => $event->public_uuid,
                'name' => $event->name,
                'date' => $event->event_date?->toDateString(),
                'rules' => $event->rules,
            ],
            'venue' => $this->venuePayload($event->venue),
            'spaces' => $event->eventSpaces->map(fn ($space): array => [
                'id' => $space->id,
                'name' => $space->name,
                'type' => $space->type,
                'position_x' => (float) $space->position_x,
                'position_y' => (float) $space->position_y,
                'width' => (int) $space->width,
                'height' => (int) $space->height,
            ])->all(),
        ]);
    }

    /**
     * Muestra la invitación pública asociada con un token QR personal.
     */
    public function invitation(string $qrToken): Response
    {
        return Inertia::render('Public/GuestInvitation', [
            'qrToken' => $qrToken,
        ]);
    }

    /**
     * Muestra la invitación grupal de una categoría a partir de su UUID público.
     */
    public function categoryInvitation(string $publicUuid): Response
    {
        return Inertia::render('Public/CategoryInvitation', [
            'publicUuid' => $publicUuid,
        ]);
    }

    /**
     * Devuelve los invitados de una categoría y el resumen de respuestas de su invitación grupal.
     */
    public function showCategory(string $publicUuid): JsonResponse
    {
        $category = $this->findCategoryByToken($publicUuid);

        if (! $category instanceof GuestCategory) {
            return response()->json([
                'message' => 'Invitación de categoría no encontrada',
            ], 404);
        }

        $category->load(['event.venue', 'guests' => fn ($query) => $query->orderBy('name')]);

        return response()->json($this->categoryPayload($category));
    }

    /**
     * Registra la respuesta de un invitado a la invitación grupal de su categoría.
     */
    public function respondToCategoryInvitation(Request $request, string $publicUuid): JsonResponse
    {
        $validated = $request->validate([
            'guest_id' => ['required', 'integer'],
            'status' => ['required', Rule::in(Guest::statuses())],
        ]);

        $category = $this->findCategoryByToken($publicUuid);

        if (! $category instanceof GuestCategory) {
            return response()->json([
                'message' => 'Invitación de categoría no encontrada',
            ], 404);
        }

        $guest = Guest::query()
            ->whereKey($validated['guest_id'])
            ->where('category_id', $category->id)
            ->first();

        if (! $guest instanceof Guest) {
            throw ValidationException::withMessages([
                'guest_id' => 'El invitado no pertenece a esta categoría.',
            ]);
        }

        DB::transaction(function () use ($guest, $validated): void {
            Guest::query()
                ->whereKey($guest->id)
                ->lockForUpdate()
                ->update(['invitation_status' => $validated['status']]);
        });

        $category->load(['event.venue', 'guests' => fn ($query) => $query->orderBy('name')]);

        return response()->json([
            'message' => 'Respuesta registrada.',
            'guest' => [
                'id' => $guest->id,
                'name' => $guest->name,
                'status' => $validated['status'],
                'status_label' => Guest::statusLabel($validated['status']),
            ],
            ...$this->categoryPayload($category),
        ]);
    }

    /**
     * Devuelve los datos públicos del evento a partir de su UUID público.
     */
    public function showEvent(string $publicUuid): JsonResponse
    {
        $event = Event::query()
            ->with(['venue', 'eventSpaces', 'guestCategories'])
            ->whereRaw('lower(public_uuid) = ?', [strtolower($publicUuid)])
            ->first();

        if (! $event instanceof Event) {
            return response()->json([
                'message' => 'Evento no encontrado',
            ], 404);
        }

        return response()->json([
            'event' => [
                'id' => $event->id,
                'public_uuid' => $event->public_uuid,
                'name' => $event->name,
                'category' => $event->category,
                'date' => $event->event_date?->toDateString(),
                'rules' => $event->rules,
            ],
            'venue' => $this->venuePayload($event->venue),
            'spaces' => $event->eventSpaces->map(fn ($space): array => [
                'id' => $space->id,
                'name' => $space->name,
                'type' => $space->type,
                'position_x' => (float) $space->position_x,
                'position_y' => (float) $space->position_y,
                'width' => (int) $space->width,
                'height' => (int) $space->height,
            ])->all(),
            'guest_categories' => $event->guestCategories->map(fn ($category): array => [
                'id' => $category->id,
                'name' => $category->name,
                'public_uuid' => $category->public_uuid,
                'guest_count' => (int) $category->guest_count,
                'priority' => (int) $category->priority,
            ])->all(),
        ]);
    }

    /**
     * Busca un invitado por su token QR sin distinguir mayúsculas.
     */
    private function findGuestByToken(string $qrToken): ?Guest
    {
        return Guest::query()
            ->with(['category', 'event.venue', 'event.eventSpaces', 'table'])
            ->whereRaw('lower(qr_token) = ?', [strtolower($qrToken)])
            ->first();
    }

    /**
     * Busca una categoría por su UUID público sin distinguir mayúsculas.
     */
    private function findCategoryByToken(string $publicUuid): ?GuestCategory
    {
        return GuestCategory::query()
            ->whereRaw('lower(public_uuid) = ?', [strtolower($publicUuid)])
            ->first();
    }

    /**
     * @return array{category: array<string, mixed>, event: array<string, mixed>, guests: list<array<string, mixed>>, status_counts: array<string, int>, status_labels: array<string, string>, venue: array<string, mixed>|null}
     */
    private function categoryPayload(GuestCategory $category): array
    {
        $event = $category->event;
        $guests = $category->guests;
        $statusCounts = array_fill_keys(Guest::statuses(), 0);

        foreach ($guests as $guest) {
            $status = in_array($guest->invitation_status, Guest::statuses(), true)
                ? $guest->invitation_status
                : Guest::STATUS_PENDING;
            $statusCounts[$status]++;
        }

        return [
            'category' => [
                'id' => $category->id,
                'name' => $category->name,
                'public_uuid' => $category->public_uuid,
                'guest_count' => (int) $category->guest_count,
                'total' => $guests->count(),
            ],
            'event' => [
                'id' => $event->id,
                'public_uuid' => $event->public_uuid,
                'name' => $event->name,
                'date' => $event->event_date?->toDateString(),
                'rules' => $event->rules,
            ],
            'guests' => $guests->map(fn (Guest $guest): array => [
                'id' => $guest->id,
                'name' => $guest->name,
                'status' => $guest->invitation_status,
                'status_label' => Guest::statusLabel($guest->invitation_status),
            ])->all(),
            'status_counts' => $statusCounts,
            'status_labels' => [
                Guest::STATUS_PENDING => Guest::statusLabel(Guest::STATUS_PENDING),
                Guest::STATUS_CONFIRMED => Guest::statusLabel(Guest::STATUS_CONFIRMED),
                Guest::STATUS_REJECTED => Guest::statusLabel(Guest::STATUS_REJECTED),
            ],
            'venue' => $this->venuePayload($event->venue),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function venuePayload(?Venue $venue): ?array
    {
        if (! $venue instanceof Venue) {
            return null;
        }

        return [
            'name' => $venue->name,
            'address' => $venue->address,
            'lat' => $venue->lat === null ? null : (float) $venue->lat,
            'lng' => $venue->lng === null ? null : (float) $venue->lng,
            'google_maps_url' => $venue->google_maps_url,
        ];
    }
}
