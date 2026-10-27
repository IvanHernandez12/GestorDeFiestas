<?php

/**
 * @company Bigbitbyte
 * @authors Ivan Hernández Hinojosa, Jorge Darien Cabrera Bastidas
 * @created 2026
 * @description Guarda las propuestas elegidas y sus datos de seguimiento por evento.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agrega la columna JSON para proveedores seleccionados.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('eventos', function (Blueprint $table): void {
            $table->json('proveedores_seleccionados')->nullable();
        });
    }

    /**
     * Elimina la columna JSON de proveedores seleccionados.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('eventos', function (Blueprint $table): void {
            $table->dropColumn('proveedores_seleccionados');
        });
    }
};
