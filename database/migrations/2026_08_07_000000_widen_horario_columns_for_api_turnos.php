<?php
/**
 * @file 2026_08_07_000000_widen_horario_columns_for_api_turnos.php
 * @description Amplía la columna "horario" en tour_fechas y reserva_tours de varchar(10) a varchar(50). El límite de 10 caracteres alcanzaba para horas "HH:MM" pero no para los "Turno" de Unique (ej. "Turno Unico", "1er Turno"), que se guardan tal cual como horario cuando el tour proviene de una API externa.
 * @date 2026-08-07
 * @author Claude
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
        Schema::table('tour_fechas', function (Blueprint $table) {
            $table->string('horario', 50)->default('09:00')->change();
        });

        Schema::table('reserva_tours', function (Blueprint $table) {
            $table->string('horario', 50)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tour_fechas', function (Blueprint $table) {
            $table->string('horario', 10)->default('09:00')->change();
        });

        Schema::table('reserva_tours', function (Blueprint $table) {
            $table->string('horario', 10)->nullable()->change();
        });
    }
};
