<?php

/**
 * @empresa Bigbitbyte
 *
 * @autor Ivan Hernandez Hinojosa
 *
 * @creado 14/noviembre/2026
 *
 * @modificado 25/noviembre/2026
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
                'providers' => ['100% Zana Catering', 'Salón de Eventos La Loma', 'Grupo Alfamusic', 'Jhovanni Flores Cake Designer', 'Pista Iluminada Toluca'],
            ],
            [
                'name' => 'Boda Premium',
                'category' => 'Boda',
                'providers' => ['Operado en Gastronomía', 'Casa Magaya', 'Celebra Studio', 'Grupo Alfamusic', 'Jhovanni Flores Cake Designer'],
            ],
            [
                'name' => 'Fiesta Infantil Básica',
                'category' => 'Cumpleaños',
                'providers' => ['Inflables Toin', 'Payasos en Toluca', 'Taquizas y Banquetes Marisol', 'Pasteles Toluca', 'Mededul (Mesa Dulces)'],
            ],
            [
                'name' => 'Cumpleaños Adulto',
                'category' => 'Cumpleaños',
                'providers' => ['Mexicazuelas (Taquiza)', 'Luz y Sonido Toluca', 'Rycco Rycco Pastel'],
            ],
            [
                'name' => 'Graduación Clásica',
                'category' => 'Graduación',
                'providers' => ['Villa Charra de Toluca', 'Vizualméxico Fotografía'],
            ],
            [
                'name' => 'XV Años Jardín',
                'category' => 'XV años',
                'providers' => ['Finca Santa Lucia', 'Vizualméxico Fotografía'],
            ],
        ];

        foreach ($packages as $packageData) {
            $providers = Provider::query()
                ->whereIn('name', $packageData['providers'])
                ->get()
                ->keyBy('name');
            $providerPrices = [];
            $estimatedPriceInCents = 0;

            foreach ($packageData['providers'] as $providerName) {
                $provider = $providers->get($providerName);

                if ($provider instanceof Provider) {
                    $price = (float) ($provider->price ?? 0);
                    $providerPrices[$provider->id] = ['price' => $price];
                    $estimatedPriceInCents += (int) round($price * 100);
                }
            }

            $package = Package::updateOrCreate(
                ['name' => $packageData['name']],
                [
                    'category' => $packageData['category'],
                    'estimated_price' => $estimatedPriceInCents / 100,
                ]
            );

            $package->providers()->sync($providerPrices);
        }
    }
}
