<?php

/**
 * @company Bigbitbyte
 * @authors Ivan Hernández Hinojosa
 * @created 20/octubre/2026
 * @description Define los endpoints REST de eventos e invitados.
 */

use App\Http\Controllers\Api\EventoController;
use App\Http\Controllers\Api\InvitadoController;
use Illuminate\Support\Facades\Route;

Route::apiResource('eventos', EventoController::class);
Route::apiResource('eventos.invitados', InvitadoController::class)->except(['index', 'show']);
Route::get('eventos/{evento}/invitados', [InvitadoController::class, 'index']);
Route::get('eventos/{evento}/invitados/{invitado}', [InvitadoController::class, 'show']);
