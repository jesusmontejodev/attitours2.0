<?php
/**
 * @file 2026_07_31_000001_add_flexible_anticipo_and_private_calendar.php
 * @description Migración para añadir el campo anticipo_porcentaje en tours y es_privado en tour_fechas.
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
        // 1. Añadir anticipo_porcentaje en la tabla 'tours'
        Schema::table('tours', function (Blueprint $table) {
            $table->integer('anticipo_porcentaje')->default(20)->after('tarifas_privadas');
        });

        // 2. Añadir es_privado en la tabla 'tour_fechas'
        Schema::table('tour_fechas', function (Blueprint $table) {
            $table->boolean('es_privado')->default(false)->after('horario');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tour_fechas', function (Blueprint $table) {
            $table->dropColumn('es_privado');
        });

        Schema::table('tours', function (Blueprint $table) {
            $table->dropColumn('anticipo_porcentaje');
        });
    }
};
