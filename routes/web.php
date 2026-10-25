<?php

/**
 * @company Bigbitbyte
 * @author Ivan Hernández Hinojosa
 * @created 20/octubre/2026
 * @description Define las rutas web del gestor de eventos.
 */

use App\Http\Controllers\EventoController;
use Illuminate\Support\Facades\Route;

Route::get('/', [EventoController::class, 'dashboard'])->name('eventos.dashboard');
Route::get('/eventos/crear', [EventoController::class, 'crear'])->name('eventos.crear');
Route::post('/eventos', [EventoController::class, 'store'])->name('eventos.store');
Route::get('/eventos/{evento}', [EventoController::class, 'detalle'])->name('eventos.detalle');
