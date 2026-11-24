<?php

/**
 * @empresa Bigbitbyte
 *
 * @autor Ivan Hernandez Hinojosa
 *
 * @creado 24/noviembre/2026
 *
 * @modificado 24/noviembre/2026
 *
 * @descripción Guarda el estado y las referencias de pago de paquetes de eventos.
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
        Schema::table('packages', function (Blueprint $table): void {
            $table->boolean('is_paid')->default(false)->after('estimated_price');
            $table->uuid('payment_reference')->nullable()->unique()->after('is_paid');
            $table->string('mercadopago_preference_id')->nullable()->unique()->after('payment_reference');
            $table->string('mercadopago_payment_id')->nullable()->unique()->after('mercadopago_preference_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table): void {
            $table->dropUnique(['payment_reference']);
            $table->dropUnique(['mercadopago_preference_id']);
            $table->dropUnique(['mercadopago_payment_id']);
            $table->dropColumn([
                'is_paid',
                'payment_reference',
                'mercadopago_preference_id',
                'mercadopago_payment_id',
            ]);
        });
    }
};
