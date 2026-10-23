<?php

/**
 * @company Bigbitbyte
 * @authors Ivan Hernández Hinojosa, Jorge Darien Cabrera Bastidas, Carlos González Laines
 * @created 2026
 * @description Coordina los datos de demostración del sistema.
 */

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run(): void
    {
        $this->call(EventoSeeder::class);

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
    }
}
