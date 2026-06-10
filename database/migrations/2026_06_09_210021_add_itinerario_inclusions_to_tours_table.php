<?php
/**
 * @file 2026_06_09_210021_add_itinerario_inclusions_to_tours_table.php
 * @description Agrega las columnas 'itinerario', 'incluye' y 'no_incluye' (en formato JSON) a la tabla de tours para soportar itinerarios dinámicos e inclusiones/exclusiones administrables.
 * @date 2026-06-09
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
            $table->json('itinerario')->nullable()->after('tags');
            $table->json('incluye')->nullable()->after('itinerario');
            $table->json('no_incluye')->nullable()->after('incluye');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tours', function (Blueprint $table) {
            $table->dropColumn(['itinerario', 'incluye', 'no_incluye']);
        });
    }
};
