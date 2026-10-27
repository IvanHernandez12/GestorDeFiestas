<?php

/**
 * @company Bigbitbyte
 * @authors Ivan Hernández Hinojosa, Jorge Darien Cabrera Bastidas, Carlos González Laines
 * @created 2026
 * @description Agrega la columna de distribución del salón al modelo de eventos para persistir mesas y croquis.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecuta las migraciones.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('eventos', function (Blueprint $table): void {
            $table->json('distribucion_salon')->nullable()->after('prioridad_invitados');
        });
    }

    /**
     * Revierte las migraciones.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('eventos', function (Blueprint $table): void {
            $table->dropColumn('distribucion_salon');
        });
    }
};
