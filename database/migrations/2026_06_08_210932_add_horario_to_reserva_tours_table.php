<?php
/**
 * @file 2026_06_08_210932_add_horario_to_reserva_tours_table.php
 * @description Agrega la columna horario a la tabla de detalles de reserva (reserva_tours) para saber el horario seleccionado de salida.
 * @date 2026-06-08
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
        Schema::table('reserva_tours', function (Blueprint $table) {
            $table->string('horario', 10)->nullable()->after('fecha_seleccionada');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reserva_tours', function (Blueprint $table) {
            $table->dropColumn('horario');
        });
    }
};
