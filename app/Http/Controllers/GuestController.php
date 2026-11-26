<?php

/**
 * @empresa Bigbitbyte
 *
 * @autor Ivan Hernandez Hinojosa
 *
 * @creado 20/noviembre/2026
 *
 * @modificado 25/noviembre/2026
 *
 * @descripción Lista, registra y asigna sillas a los invitados de eventos propios.
 */

namespace App\Http\Controllers;

use App\Mail\GuestInvitation;
use App\Models\Event;
use App\Models\Guest;
use App\Models\User;
use App\Services\PlanLimitService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class GuestController extends Controller
{
    /**
     * Crea el controlador con el servicio de límites inyectado.
     */
    public function __construct(private PlanLimitService $planLimitService) {}

    /**
     * Lista los invitados de un evento que pertenece al usuario autenticado.
     *
     * @return JsonResponse<array{guests: Collection<int, Guest>}>
     */
    public function index(Request $request, int $eventId): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $event = $user->events()->findOrFail($eventId);
        $guests = $event->guests()
            ->with(['category', 'table'])
            ->orderBy('name')
            ->get();

        return response()->json(['guests' => $guests]);
    }

    /**
     * Crea un invitado con un token QR único para un evento propio.
     *
     * @return JsonResponse<array{guest: Guest}>
     */
    public function store(Request $request, int $eventId): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $event = $user->events()->findOrFail($eventId);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'category_id' => [
                'required',
                'integer',
                Rule::exists('guest_categories', 'id')->where('event_id', $event->id),
            ],
        ]);

        $guest = DB::transaction(function () use ($user, $event, $validated): Guest {
            $lockedUser = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $lockedEvent = Event::query()->whereKey($event->id)->lockForUpdate()->firstOrFail();
            $category = $lockedEvent->guestCategories()
                ->whereKey($validated['category_id'])
                ->lockForUpdate()
                ->firstOrFail();

            $categoryGuestCount = $category->guests()->count();

            if ($categoryGuestCount >= (int) $category->guest_count) {
                throw ValidationException::withMessages([
                    'category_id' => "La categoría «{$category->name}» ya alcanzó su cupo de {$category->guest_count} invitados.",
                ]);
            }

            $limitError = $this->planLimitService->guestCreationError($lockedUser, $lockedEvent);

            if ($limitError !== null) {
                throw ValidationException::withMessages(['guest_count' => $limitError]);
            }

            return $lockedEvent->guests()->create([
                ...$validated,
                'qr_token' => (string) Str::uuid(),
            ]);
        });

        return response()->json([
            'guest' => $guest->load('category'),
        ], 201);
    }

    /**
     * Envía la invitación personal con su código QR a un invitado con correo registrado.
     *
     * @return JsonResponse<array{email: string, invitation_url: string, message: string}>
     */
    public function sendInvitation(Request $request, int $eventId, int $guestId): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $event = $user->events()->findOrFail($eventId);
        $guest = $event->guests()->findOrFail($guestId);
        $validated = $request->validate([
            'qr_png' => [
                'required',
                'string',
                'max:20000',
                'regex:/^data:image\/png;base64,[A-Za-z0-9+\/=]+$/',
            ],
        ]);

        if (! filter_var($guest->email, FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::withMessages([
                'email' => 'El invitado no tiene un correo válido registrado.',
            ]);
        }

        $guest->load('event.venue');
        $invitationUrl = route('public.invitations.show', ['qr_token' => $guest->qr_token]);

        Mail::to($guest->email)->send(new GuestInvitation(
            $guest,
            $invitationUrl,
            $validated['qr_png'],
        ));

        return response()->json([
            'message' => 'Invitación enviada correctamente.',
            'email' => $guest->email,
            'invitation_url' => $invitationUrl,
        ]);
    }

    /**
     * Asigna o libera la silla reservada de un invitado de un evento propio.
     *
     * @return JsonResponse<array{guest: Guest}>
     */
    public function assignSeat(Request $request, int $eventId, int $guestId): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $event = $user->events()->findOrFail($eventId);
        $validated = $request->validate([
            'table_id' => ['nullable', 'integer'],
            'seat_number' => ['nullable', 'integer', 'min:1', 'max:255'],
        ]);

        $tableId = $validated['table_id'] ?? null;
        $seatNumber = $validated['seat_number'] ?? null;

        if (($tableId === null) !== ($seatNumber === null)) {
            throw ValidationException::withMessages([
                'seat_number' => 'La mesa y el número de silla deben indicarse juntos.',
            ]);
        }

        $guest = DB::transaction(function () use ($event, $guestId, $tableId, $seatNumber): Guest {
            $lockedEvent = Event::query()->whereKey($event->id)->lockForUpdate()->firstOrFail();
            $guest = $lockedEvent->guests()->whereKey($guestId)->lockForUpdate()->firstOrFail();

            if ($tableId !== null) {
                $table = $lockedEvent->tables()->whereKey($tableId)->lockForUpdate()->firstOrFail();

                if ($seatNumber > $table->capacity) {
                    throw ValidationException::withMessages([
                        'seat_number' => 'El número de silla excede el aforo de la mesa.',
                    ]);
                }

                $occupant = $lockedEvent->guests()
                    ->where('table_id', $tableId)
                    ->where('seat_number', $seatNumber)
                    ->whereKeyNot($guest->id)
                    ->lockForUpdate()
                    ->first();

                $occupant?->update([
                    'table_id' => null,
                    'seat_number' => null,
                ]);
            }

            $guest->update([
                'table_id' => $tableId,
                'seat_number' => $seatNumber,
            ]);

            return $guest;
        });

        return response()->json([
            'guest' => $guest->load(['category', 'table']),
        ]);
    }
}
