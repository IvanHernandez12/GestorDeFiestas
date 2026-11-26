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
 * @descripción Agrega a cada invitado la mesa y silla reservada en el plano.
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
        Schema::table('guests', function (Blueprint $table): void {
            $table->foreignId('table_id')->nullable()->constrained('tables')->nullOnDelete();
            $table->unsignedTinyInteger('seat_number')->nullable();
            $table->unique(['table_id', 'seat_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('guests', function (Blueprint $table): void {
            $table->dropUnique(['table_id', 'seat_number']);
            $table->dropColumn(['table_id', 'seat_number']);
        });
    }
};
