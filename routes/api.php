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
 * @descripción Define rutas de autenticación y acceso a la API.
 */

use App\Http\Controllers\AuthController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\EventPackageController;
use App\Http\Controllers\GuestController;
use App\Http\Controllers\PublicGuestController;
use App\Http\Controllers\VenueController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->controller(AuthController::class)->group(function (): void {
    Route::post('/register', 'register')->name('auth.register');
    Route::post('/login', 'login')->middleware('throttle:6,1')->name('auth.login');
    Route::post('/logout', 'logout')->middleware('auth:sanctum')->name('auth.logout');
});

Route::get('/venues/search', [VenueController::class, 'search'])->name('venues.search');
Route::get('/public/guest/{qr_token}', [PublicGuestController::class, 'show'])
    ->name('public.guests.show');
Route::get('/events', [EventController::class, 'index'])->middleware('auth:sanctum')->name('events.index');
Route::post('/events', [EventController::class, 'store'])
    ->middleware(['auth:sanctum', 'check.plan.limits'])
    ->name('events.store');
Route::get('/events/{eventId}/guests', [GuestController::class, 'index'])
    ->whereNumber('eventId')
    ->middleware('auth:sanctum')
    ->name('events.guests.index');
Route::post('/events/{eventId}/guests', [GuestController::class, 'store'])
    ->whereNumber('eventId')
    ->middleware('auth:sanctum')
    ->name('events.guests.store');
Route::get('/events/{id}', [EventController::class, 'show'])
    ->whereNumber('id')
    ->middleware('auth:sanctum')
    ->name('events.show');
Route::get('/events/{eventId}/packages', [EventPackageController::class, 'index'])
    ->whereNumber('eventId')
    ->middleware('auth:sanctum')
    ->name('events.packages.index');
Route::post('/events/{eventId}/packages/{packageId}/confirm', [EventPackageController::class, 'store'])
    ->whereNumber(['eventId', 'packageId'])
    ->middleware('auth:sanctum')
    ->name('events.packages.confirm');

Route::get('/user', function (Request $request): JsonResponse {
    return response()->json($request->user());
})->middleware('auth:sanctum');
