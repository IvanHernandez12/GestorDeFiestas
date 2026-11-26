<?php

/**
 * @empresa Bigbitbyte
 *
 * @autor Ivan Hernandez Hinojosa
 *
 * @creado 25/noviembre/2026
 *
 * @modificado 25/noviembre/2026
 *
 * @descripción Agrega forma y dimensiones configurables a las mesas de un evento.
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
        Schema::table('tables', function (Blueprint $table): void {
            $table->string('shape')->default('round');
            $table->unsignedSmallInteger('width')->default(76);
            $table->unsignedSmallInteger('height')->default(76);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tables', function (Blueprint $table): void {
            $table->dropColumn(['shape', 'width', 'height']);
        });
    }
};
