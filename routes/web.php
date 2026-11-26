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
 * @descripción Define las rutas web de autenticación y administración de eventos.
 */

use App\Http\Controllers\EventSpaceController;
use App\Http\Controllers\EventWebController;
use App\Http\Controllers\PaymentSimulationController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\PublicGuestController;
use App\Http\Controllers\WebAuthController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Route;

Route::get('/', function (): RedirectResponse {
    return to_route(auth()->check() ? 'web.events.index' : 'login');
});

Route::get('/invitacion/{qr_token}', [PublicGuestController::class, 'invitation'])
    ->whereUuid('qr_token')
    ->name('public.invitations.show');
Route::get('/invitacion/categoria/{public_uuid}', [PublicGuestController::class, 'categoryInvitation'])
    ->whereUuid('public_uuid')
    ->name('public.invitations.category');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [WebAuthController::class, 'create'])->name('login');
    Route::get('/register', [WebAuthController::class, 'createRegistration'])->name('register');
    Route::post('/login', [WebAuthController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('login.store');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [WebAuthController::class, 'destroy'])->name('logout');
    Route::get('/events', [EventWebController::class, 'index'])->name('web.events.index');
    Route::get('/events/create', [EventWebController::class, 'create'])->name('web.events.create');
    Route::get('/plans', [PlanController::class, 'index'])->name('plans.index');
    Route::post('/plans/{plan}/subscribe', [PlanController::class, 'subscribe'])
        ->whereNumber('plan')
        ->name('plans.subscribe');
    Route::get('/simulate-payment', [PaymentSimulationController::class, 'show'])
        ->name('payments.simulate');
    Route::post('/simulate-payment/confirm', [PaymentSimulationController::class, 'confirm'])
        ->name('payments.simulate.confirm');
    Route::delete('/events/{eventId}', [EventWebController::class, 'destroy'])
        ->whereNumber('eventId')
        ->name('web.events.destroy');
    Route::get('/events/{eventId}', [EventWebController::class, 'show'])
        ->whereNumber('eventId')
        ->name('web.events.show');
    Route::get('/events/{eventId}/packages', [EventWebController::class, 'selectPackage'])
        ->whereNumber('eventId')
        ->name('web.events.packages.select');
    Route::patch('/events/{eventId}/tables/{tableId}/position', [EventWebController::class, 'updateTablePosition'])
        ->whereNumber(['eventId', 'tableId'])
        ->name('web.events.tables.position');
    Route::post('/events/{eventId}/tables', [EventWebController::class, 'storeTable'])
        ->whereNumber('eventId')
        ->name('web.events.tables.store');
    Route::patch('/events/{eventId}/tables/{tableId}', [EventWebController::class, 'updateTable'])
        ->whereNumber(['eventId', 'tableId'])
        ->name('web.events.tables.update');
    Route::delete('/events/{eventId}/tables/{tableId}', [EventWebController::class, 'destroyTable'])
        ->whereNumber(['eventId', 'tableId'])
        ->name('web.events.tables.destroy');
    Route::post('/events/{eventId}/spaces', [EventSpaceController::class, 'store'])
        ->whereNumber('eventId')
        ->name('web.events.spaces.store');
    Route::patch('/events/{eventId}/spaces/{spaceId}', [EventSpaceController::class, 'update'])
        ->whereNumber(['eventId', 'spaceId'])
        ->name('web.events.spaces.update');
    Route::delete('/events/{eventId}/spaces/{spaceId}', [EventSpaceController::class, 'destroy'])
        ->whereNumber(['eventId', 'spaceId'])
        ->name('web.events.spaces.destroy');
});
