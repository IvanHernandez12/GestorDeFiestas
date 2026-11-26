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
 * @descripción Agrega un token público a cada categoría para compartir una invitación grupal por QR.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('guest_categories', function (Blueprint $table): void {
            $table->uuid('public_uuid')->nullable()->unique();
        });

        DB::table('guest_categories')
            ->orderBy('id')
            ->chunkById(100, function ($categories): void {
                foreach ($categories as $category) {
                    DB::table('guest_categories')
                        ->where('id', $category->id)
                        ->update(['public_uuid' => (string) Str::uuid()]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('guest_categories', function (Blueprint $table): void {
            $table->dropUnique(['public_uuid']);
            $table->dropColumn('public_uuid');
        });
    }
};
