<?php
/**
 * @file 2026_09_07_000000_fix_tour_fechas_unique_index_add_es_privado.php
 * @description El índice único de tour_fechas quedó como (tour_id, fecha, horario) desde antes de
 * que existiera la columna es_privado (ver 2026_07_31_000001_add_flexible_anticipo_and_private_calendar.php),
 * así que nunca se actualizó para incluirla. Un tour "Mixto" (tipo_modalidad = ambos) necesita una fila
 * compartida y una privada con el mismo horario en la misma fecha, pero el índice viejo lo impide: al
 * habilitar fechas en lote (o por día) para el lado privado con un horario ya usado por el compartido,
 * el INSERT choca contra el único existente y la operación falla con un error de integridad.
 * @date 2026-09-07
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
        // El índice viejo sostiene la FK de tour_id, así que hay que crear el nuevo primero:
        // ambos arrancan con tour_id, así que el nuevo puede tomar el relevo antes de borrar el viejo.
        Schema::table('tour_fechas', function (Blueprint $table) {
            $table->unique(['tour_id', 'fecha', 'horario', 'es_privado']);
        });

        Schema::table('tour_fechas', function (Blueprint $table) {
            $table->dropUnique(['tour_id', 'fecha', 'horario']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tour_fechas', function (Blueprint $table) {
            $table->unique(['tour_id', 'fecha', 'horario']);
        });

        Schema::table('tour_fechas', function (Blueprint $table) {
            $table->dropUnique(['tour_id', 'fecha', 'horario', 'es_privado']);
        });
    }
};
