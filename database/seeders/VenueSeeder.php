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
 * @descripción Carga diez salones de prueba en Toluca, Metepec y Tianguistenco.
 */

namespace Database\Seeders;

use App\Models\Venue;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VenueSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $venues = [
            [
                'name' => 'Salon Altezz',
                'address' => 'Marie Curie 415, San Juan Buenavista, Santa Ana Tlapaltitlán, 50060 Toluca de Lerdo, Méx.',
                'lat' => 19.299736756248002,
                'lng' => -99.6300072,
                'capacity' => 180,
                'min_price' => 28000,
                'max_price' => 28000,
            ],
            [
                'name' => 'Salon el Colibri',
                'address' => 'Priv. Matlazincas 184 Privada, Barrio de San Luis Obispo, 50040 Toluca de Lerdo, Méx.',
                'lat' => 19.305249236151557,
                'lng' => -99.66002431071469,
                'capacity' => 30,
                'min_price' => 13530,
                'max_price' => 13530,
            ],
            [
                'name' => 'Salones de fiestas Alvi Toluca',
                'address' => 'Via José López Portillo 318, Delegación San Lorenzo Tepaltitlán I, 50010 San Lorenzo Tepaltitlán, Méx.',
                'lat' => 19.323427726725964,
                'lng' => -99.62627866955255,
                'capacity' => 140,
                'min_price' => 19600,
                'max_price' => 19600,
            ],
            [
                'name' => 'Anturios Salón y Jardín',
                'address' => 'P.º Cristóbal Colón 612, Villa Hogar, 50170 Toluca de Lerdo, Méx.',
                'lat' => 19.270001098581346,
                'lng' => -99.66144533649933,
                'capacity' => 120,
                'min_price' => 23000,
                'max_price' => 23000,
            ],
            [
                'name' => 'Salón Duraznos Metepec',
                'address' => 'Av. Lic. Benito Juárez García 532, San Mateo, 52140 Metepec, Méx.',
                'lat' => 19.261464933405623,
                'lng' => -99.61585636138172,
                'capacity' => 50,
                'min_price' => 8000,
                'max_price' => 8000,
            ],
            [
                'name' => 'Quinta Santa Teresa',
                'address' => 'C. Moctezuma 36-37, Santa Cruz, 52140 Metepec, Méx.',
                'lat' => 19.25871002133181,
                'lng' => -99.60195178946192,
                'capacity' => 180,
                'min_price' => 26000,
                'max_price' => 26000,
            ],
            [
                'name' => 'Salón Los Amates Metepec',
                'address' => 'C. Manuel J. Clouthier 973, Providencia, Coaxustenco, 52176 Metepec, Méx.',
                'lat' => 19.268566089056335,
                'lng' => -99.60070534340792,
                'capacity' => 90,
                'min_price' => 18000,
                'max_price' => 18000,
            ],
            [
                'name' => "Salón Vicky's",
                'address' => 'Ron de Hank, Prolongación, Don Catarino González Benítez, Colonia Guadalupe, 52600 Santiago Tianguistenco de Galeana, Méx.',
                'lat' => 19.17866193918337,
                'lng' => -99.47531499101308,
                'capacity' => 100,
                'min_price' => 20000,
                'max_price' => 20000,
            ],
            [
                'name' => 'Jardín la Cabaña Eventos Sociales',
                'address' => '1ra Cda. de Catarino Gonzalez 7, Tianguistenco de Galeana, 52600 Santiago Tianguistenco de Galeana, Méx.',
                'lat' => 19.17817553659086,
                'lng' => -99.46450032396437,
                'capacity' => 40,
                'min_price' => 7000,
                'max_price' => 7000,
            ],
            [
                'name' => 'Salón Santiago Palace',
                'address' => '5 de Febrero Manzana 046, Tianguistenco de Galeana, 52600 Santiago Tianguistenco de Galeana, Méx.',
                'lat' => 19.185309297326715,
                'lng' => -99.46312703291058,
                'capacity' => 70,
                'min_price' => 14000,
                'max_price' => 14000,
            ],
        ];

        DB::transaction(function () use ($venues): void {
            foreach ($venues as $venue) {
                Venue::query()->updateOrCreate(
                    ['name' => $venue['name']],
                    $venue,
                );
            }

            Venue::query()->whereNotIn('name', array_column($venues, 'name'))->delete();
        });
    }
}
