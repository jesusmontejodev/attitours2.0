<?php
/**
 * @file 2026_06_08_215600_add_horario_to_tour_fechas_table.php
 * @description Agrega la columna horario a la tabla tour_fechas y actualiza el índice único para que sea compuesto por tour_id, fecha y horario.
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
        Schema::disableForeignKeyConstraints();
        
        Schema::table('tour_fechas', function (Blueprint $table) {
            $table->dropUnique('tour_fechas_tour_id_fecha_unique');
            $table->string('horario', 10)->default('09:00')->after('fecha');
            $table->unique(['tour_id', 'fecha', 'horario']);
        });
        
        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        
        Schema::table('tour_fechas', function (Blueprint $table) {
            $table->dropUnique(['tour_id', 'fecha', 'horario']);
            $table->dropColumn('horario');
            $table->unique(['tour_id', 'fecha']);
        });
        
        Schema::enableForeignKeyConstraints();
    }
};
