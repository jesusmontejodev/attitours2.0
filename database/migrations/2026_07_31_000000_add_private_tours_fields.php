<?php
/**
 * @file 2026_07_31_000000_add_private_tours_fields.php
 * @description Migración para añadir campos de soporte a tours privados en la base de datos (tours, reservas y detalles de reserva).
 * @date 2026-07-31
 * @author Antigravity
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
        // 1. Modificaciones a la tabla 'tours'
        Schema::table('tours', function (Blueprint $table) {
            $table->string('tipo_modalidad')->default('compartido')->after('precio_base_usd');
            $table->json('tarifas_privadas')->nullable()->after('tipo_modalidad');
        });

        // 2. Modificaciones a la tabla 'reservas'
        Schema::table('reservas', function (Blueprint $table) {
            $table->decimal('monto_pagado_online_usd', 10, 2)->default(0.00)->after('precio_total_usd');
            $table->decimal('monto_pendiente_destino_usd', 10, 2)->default(0.00)->after('monto_pagado_online_usd');
        });

        // 3. Modificaciones a la tabla 'reserva_tours'
        Schema::table('reserva_tours', function (Blueprint $table) {
            $table->boolean('es_privado')->default(false)->after('cantidad_personas');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reserva_tours', function (Blueprint $table) {
            $table->dropColumn('es_privado');
        });

        Schema::table('reservas', function (Blueprint $table) {
            $table->dropColumn(['monto_pagado_online_usd', 'monto_pendiente_destino_usd']);
        });

        Schema::table('tours', function (Blueprint $table) {
            $table->dropColumn(['tipo_modalidad', 'tarifas_privadas']);
        });
    }
};
