<?php
/**
 * @file 2026_09_07_000001_widen_tour_id_columns.php
 * @description El id de tours se genera con Str::slug(titulo_es) (hasta 150 caracteres) prefijado
 * con "tour_", pero la columna era varchar(50): con títulos largos el INSERT fallaba con
 * "Data too long for column 'id'". Se amplía tours.id y todas sus columnas FK tour_id a varchar(191).
 * @date 2026-09-07
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
        Schema::table('tours', function (Blueprint $table) {
            $table->string('id', 191)->change();
        });

        Schema::table('tour_fechas', function (Blueprint $table) {
            $table->string('tour_id', 191)->change();
        });

        Schema::table('reserva_tours', function (Blueprint $table) {
            $table->string('tour_id', 191)->change();
        });

        Schema::table('tours_importados', function (Blueprint $table) {
            $table->string('tour_id', 191)->nullable()->change();
        });

        Schema::table('tour_cambios_precio_api', function (Blueprint $table) {
            $table->string('tour_id', 191)->change();
        });

        Schema::table('tour_api_notificaciones', function (Blueprint $table) {
            $table->string('tour_id', 191)->change();
        });

        Schema::table('tour_disponibilidad_syncs', function (Blueprint $table) {
            $table->string('tour_id', 191)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tour_disponibilidad_syncs', function (Blueprint $table) {
            $table->string('tour_id', 50)->change();
        });

        Schema::table('tour_api_notificaciones', function (Blueprint $table) {
            $table->string('tour_id', 50)->change();
        });

        Schema::table('tour_cambios_precio_api', function (Blueprint $table) {
            $table->string('tour_id', 50)->change();
        });

        Schema::table('tours_importados', function (Blueprint $table) {
            $table->string('tour_id', 50)->nullable()->change();
        });

        Schema::table('reserva_tours', function (Blueprint $table) {
            $table->string('tour_id', 50)->change();
        });

        Schema::table('tour_fechas', function (Blueprint $table) {
            $table->string('tour_id', 50)->change();
        });

        Schema::table('tours', function (Blueprint $table) {
            $table->string('id', 50)->change();
        });
    }
};
