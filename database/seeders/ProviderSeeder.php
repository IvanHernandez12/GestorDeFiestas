<?php

/**
 * @empresa Bigbitbyte
 *
 * @autor Ivan Hernandez Hinojosa
 *
 * @creado 14/noviembre/2026
 *
 * @modificado 24/noviembre/2026
 *
 * @descripción Llena la tabla de proveedores basados en el catálogo real de Bigbitbyte.
 */

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProviderSeeder extends Seeder
{
    public function run(): void
    {
        $providers = [
            // BODAS
            ['name' => '100% Zana Catering', 'category' => 'Banquete', 'price' => 35000, 'capacity' => 100],
            ['name' => 'Operado en Gastronomía', 'category' => 'Banquete', 'price' => 45000, 'capacity' => 100],
            ['name' => 'Casa Magaya', 'category' => 'Jardín', 'price' => 60000, 'capacity' => 100],
            ['name' => 'Salón de Eventos La Loma', 'category' => 'Salón', 'price' => 23000, 'capacity' => 100],
            ['name' => 'Grupo Alfamusic', 'category' => 'Sonido', 'price' => 29800, 'capacity' => 100],
            ['name' => 'Pista Iluminada Toluca', 'category' => 'Pista', 'price' => 6250, 'capacity' => 120],
            ['name' => 'Jhovanni Flores Cake Designer', 'category' => 'Repostería', 'price' => 9800, 'capacity' => 100],
            ['name' => 'Celebra Studio', 'category' => 'Foto y Video', 'price' => 20000, 'capacity' => 100],

            // FIESTAS INFANTILES
            ['name' => 'Inflables Toin', 'category' => 'Inflables', 'price' => 1030, 'capacity' => 10],
            ['name' => 'Payasos en Toluca', 'category' => 'Animación', 'price' => 990, 'capacity' => 25],
            ['name' => 'Taquizas y Banquetes Marisol', 'category' => 'Comida', 'price' => 3950, 'capacity' => 50],
            ['name' => 'Pasteles Toluca', 'category' => 'Repostería', 'price' => 690, 'capacity' => 15],
            ['name' => 'Mededul (Mesa Dulces)', 'category' => 'Mesa de Dulces', 'price' => 4500, 'capacity' => 30],

            // CUMPLEAÑOS
            ['name' => 'Mexicazuelas (Taquiza)', 'category' => 'Comida', 'price' => 9750, 'capacity' => 50],
            ['name' => 'Luz y Sonido Toluca', 'category' => 'Sonido', 'price' => 6900, 'capacity' => 100],
            ['name' => 'Rycco Rycco Pastel', 'category' => 'Repostería', 'price' => 800, 'capacity' => 20],

            // GRADUACIONES Y 15 AÑOS
            ['name' => 'Villa Charra de Toluca', 'category' => 'Salón + Banquete', 'price' => 50000, 'capacity' => 150],
            ['name' => 'Finca Santa Lucia', 'category' => 'Jardín', 'price' => 45000, 'capacity' => 100],
            ['name' => 'Vizualméxico Fotografía', 'category' => 'Foto y Video', 'price' => 6000, 'capacity' => 100],
        ];

        foreach ($providers as $provider) {
            DB::table('providers')->updateOrInsert(
                ['name' => $provider['name']],
                array_merge($provider, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }
}
