<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Plan::query()->updateOrCreate(['slug' => 'free'], [
            'name' => 'Gratuito',
            'price' => 0,
            'max_events' => 1,
            'max_guests' => 50,
            'is_free' => true,
        ]);

        Plan::query()->updateOrCreate(['slug' => 'basic'], [
            'name' => 'Básico',
            'price' => 299,
            'max_events' => 5,
            'max_guests' => 200,
            'is_free' => false,
        ]);

        Plan::query()->updateOrCreate(['slug' => 'premium'], [
            'name' => 'Premium',
            'price' => 699,
            'max_events' => null,
            'max_guests' => 1000,
            'is_free' => false,
        ]);
    }
}
