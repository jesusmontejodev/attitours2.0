<?php
/**
 * @file 2026_06_08_210840_add_horarios_to_tours_table.php
 * @description Agrega la columna horarios de tipo JSON a la tabla de tours para programar múltiples horas de salida.
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
        Schema::table('tours', function (Blueprint $table) {
            $table->json('horarios')->nullable()->after('tags');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tours', function (Blueprint $table) {
            $table->dropColumn('horarios');
        });
    }
};
