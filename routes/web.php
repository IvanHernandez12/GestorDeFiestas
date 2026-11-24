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
 * @descripción Define las rutas web de autenticación y administración de eventos.
 */

use App\Http\Controllers\EventWebController;
use App\Http\Controllers\PaymentSimulationController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\WebAuthController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Route;

Route::get('/', function (): RedirectResponse {
    return to_route(auth()->check() ? 'web.events.index' : 'login');
});

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
});
