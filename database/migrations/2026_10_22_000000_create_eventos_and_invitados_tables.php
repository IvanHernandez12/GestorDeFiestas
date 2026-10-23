<?php

/**
 * @company Bigbitbyte
 * @authors Ivan Hernández Hinojosa, Jorge Darien Cabrera Bastidas, Carlos González Laines
 * @created 2026
 * @description Crea las tablas para eventos e invitados del sistema.
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
        Schema::create('eventos', function (Blueprint $table): void {
            $table->id();
            $table->string('folio', 20)->unique();
            $table->string('nombre', 120);
            $table->string('tipo', 40);
            $table->string('estado', 40)->default('Planificando');
            $table->date('fecha_evento');
            $table->string('ubicacion', 160);
            $table->unsignedInteger('numero_invitados')->default(0);
            $table->decimal('presupuesto', 12, 2)->default(0);
            $table->string('reglamento', 500)->nullable();
            $table->json('prioridad_invitados')->nullable();
            $table->timestamps();
        });

        Schema::create('invitados', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('evento_id')->constrained('eventos')->cascadeOnDelete();
            $table->string('nombre', 120);
            $table->string('correo', 160);
            $table->string('categoria', 30)->default('General');
            $table->timestamps();
            $table->index(['evento_id', 'categoria']);
        });
    }

    /**
     * Revierte las migraciones.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('invitados');
        Schema::dropIfExists('eventos');
    }
};
