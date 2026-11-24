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
 * @descripción Crea paquetes prearmados usando los proveedores reales.
 */

namespace Database\Seeders;

use App\Models\Package;
use App\Models\Provider;
use Illuminate\Database\Seeder;

class PackageSeeder extends Seeder
{
    public function run(): void
    {
        $packages = [
            [
                'name' => 'Boda Esencial',
                'category' => 'Boda',
                'estimated_price' => 105850, // Suma de Catering + Salón + Sonido + Pastel + Pista
                'providers' => ['100% Zana Catering', 'Salón de Eventos La Loma', 'Grupo Alfamusic', 'Jhovanni Flores Cake Designer', 'Pista Iluminada Toluca'],
            ],
            [
                'name' => 'Boda Premium',
                'category' => 'Boda',
                'estimated_price' => 164600, // Suma de Banquete + Jardín + Foto + Sonido + Pastel
                'providers' => ['Operado en Gastronomía', 'Casa Magaya', 'Celebra Studio', 'Grupo Alfamusic', 'Jhovanni Flores Cake Designer'],
            ],
            [
                'name' => 'Fiesta Infantil Básica',
                'category' => 'Cumpleaños', // O 'Fiesta Infantil'
                'estimated_price' => 10960, // Inflable + Payaso + Taquiza + Pastel + Dulces
                'providers' => ['Inflables Toin', 'Payasos en Toluca', 'Taquizas y Banquetes Marisol', 'Pasteles Toluca', 'Mededul (Mesa Dulces)'],
            ],
            [
                'name' => 'Cumpleaños Adulto',
                'category' => 'Cumpleaños',
                'estimated_price' => 17450, // Taquiza + Sonido + Pastel
                'providers' => ['Mexicazuelas (Taquiza)', 'Luz y Sonido Toluca', 'Rycco Rycco Pastel'],
            ],
            [
                'name' => 'Graduación Clásica',
                'category' => 'Graduación',
                'estimated_price' => 56000, // Salón + Foto
                'providers' => ['Villa Charra de Toluca', 'Vizualméxico Fotografía'],
            ],
            [
                'name' => 'XV Años Jardín',
                'category' => 'XV años',
                'estimated_price' => 51000, // Jardín + Foto
                'providers' => ['Finca Santa Lucia', 'Vizualméxico Fotografía'],
            ],
        ];

        foreach ($packages as $packageData) {
            $package = Package::updateOrCreate(
                ['name' => $packageData['name']],
                [
                    'category' => $packageData['category'],
                    'estimated_price' => $packageData['estimated_price'],
                ]
            );

            $providers = Provider::query()
                ->whereIn('name', $packageData['providers'])
                ->get()
                ->keyBy('name');
            $providerPrices = [];

            foreach ($packageData['providers'] as $providerName) {
                $provider = $providers->get($providerName);

                if ($provider instanceof Provider) {
                    $providerPrices[$provider->id] = ['price' => $provider->price ?? 0];
                }
            }

            $package->providers()->sync($providerPrices);
        }
    }
}
