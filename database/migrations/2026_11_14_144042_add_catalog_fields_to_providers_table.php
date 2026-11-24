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
 * @descripción Añade los campos de precio y capacidad al catálogo de proveedores.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('providers', function (Blueprint $table): void {
            $table->decimal('price', 12, 2)->nullable()->after('category');
            $table->unsignedInteger('capacity')->nullable()->after('price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('providers', function (Blueprint $table): void {
            $table->dropColumn(['price', 'capacity']);
        });
    }
};
