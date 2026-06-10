<?php
/**
 * @file 2026_06_08_213500_add_punto_encuentro_to_tours_table.php
 * @description Agrega la columna punto_encuentro a la tabla de tours para registrar la ubicación del punto de partida.
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
            $table->text('punto_encuentro')->nullable()->after('ubicacion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tours', function (Blueprint $table) {
            $table->dropColumn('punto_encuentro');
        });
    }
};
