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
 * @descripción Crea el usuario de prueba vinculado al plan gratuito.
 */

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Crea o actualiza la cuenta administrativa de prueba.
     */
    public function run(): void
    {
        $freePlan = Plan::query()->firstOrCreate(
            ['slug' => 'free'],
            [
                'name' => 'Gratuito',
                'price' => 0,
                'max_events' => 1,
                'max_guests' => 50,
                'is_free' => true,
            ],
        );

        User::query()->updateOrCreate(
            ['email' => 'ivanhernandezhinojosa@gmail.com'],
            [
                'plan_id' => $freePlan->id,
                'name' => 'Administrador Bigbitbyte',
                'password' => 'kipmm079',
            ],
        );
    }
}
