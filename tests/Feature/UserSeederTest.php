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
 * @descripción Comprueba que el seeder crea el usuario de prueba y asigna el plan gratuito.
 */

use App\Models\Plan;
use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Support\Facades\Hash;

test('seeds the Bigbitbyte administrator with the free plan', function (): void {
    $this->seed(UserSeeder::class);

    $user = User::query()->with('plan')->where('email', 'ivanhernandezhinojosa@gmail.com')->firstOrFail();

    expect($user->name)->toBe('Administrador Bigbitbyte')
        ->and($user->plan)->toBeInstanceOf(Plan::class)
        ->and($user->plan->slug)->toBe('free')
        ->and(Hash::check('kipmm079', $user->password))->toBeTrue();
});
