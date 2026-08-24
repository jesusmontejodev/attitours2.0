<?php
/**
 * @file 2026_08_22_120000_create_tour_disponibilidad_syncs_table.php
 * @description Crea la tabla de auditoría de sincronizaciones del calendario de disponibilidad
 *              (TourFecha) contra la API externa (ver TourAvailabilitySyncService::sincronizarCalendario).
 *              Registra cada corrida (manual desde el Admin, o automática al abrir la ficha pública
 *              del tour) para que el Admin pueda ver cuándo se actualizó el calendario y con qué resultado.
 * @date 2026-08-22
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
        Schema::create('tour_disponibilidad_syncs', function (Blueprint $table) {
            $table->id();
            $table->string('tour_id', 50);
            $table->unsignedBigInteger('api_conexion_id');
            $table->string('origen', 20); // manual | vista_publica
            $table->string('estado', 20); // exitoso | fallido
            $table->integer('fechas_actualizadas')->default(0);
            $table->integer('fechas_deshabilitadas')->default(0);
            $table->text('mensaje_error')->nullable();
            $table->timestamps();

            $table->foreign('tour_id')->references('id')->on('tours')->onDelete('cascade');
            $table->foreign('api_conexion_id')->references('id')->on('api_conexiones')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tour_disponibilidad_syncs');
    }
};
