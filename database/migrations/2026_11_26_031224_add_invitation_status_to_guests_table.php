<?php

/**
 * @empresa Bigbitbyte
 *
 * @autor Ivan Hernandez Hinojosa
 *
 * @creado 26/noviembre/2026
 *
 * @modificado 26/noviembre/2026
 *
 * @descripción Agrega el estado de la invitación de cada invitado para seguimiento por categoría.
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
            $table->string('invitation_status', 20)->default('pending');
            $table->index(['category_id', 'invitation_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('guests', function (Blueprint $table): void {
            $table->dropIndex(['category_id', 'invitation_status']);
            $table->dropColumn('invitation_status');
        });
    }
};
