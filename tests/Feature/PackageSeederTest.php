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
 * @descripción Verifica los paquetes de catálogo y sus proveedores asociados.
 */

use App\Models\Package;
use App\Models\Provider;
use Database\Seeders\PackageSeeder;
use Database\Seeders\ProviderSeeder;

test('seeds package providers with their catalog prices', function (): void {
    $this->seed([ProviderSeeder::class, PackageSeeder::class]);

    $package = Package::query()->where('name', 'Boda Esencial')->firstOrFail();
    $provider = Provider::query()->where('name', '100% Zana Catering')->firstOrFail();

    $this->assertDatabaseHas('package_provider', [
        'package_id' => $package->id,
        'provider_id' => $provider->id,
        'price' => 35000,
    ]);
});
