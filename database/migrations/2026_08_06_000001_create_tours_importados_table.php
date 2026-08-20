<?php
/**
 * @file 2026_08_06_000001_create_tours_importados_table.php
 * @description Crea la bandeja de tours importados desde una API externa (no implementados) pendientes de revisión por el Admin.
 * @date 2026-08-06
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
        Schema::create('tours_importados', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('api_conexion_id');
            $table->string('locacion_externa_id');
            $table->string('external_id'); // compuesto locacion:id_servicio
            $table->json('payload_raw');
            $table->string('titulo_preview')->nullable();
            $table->text('descripcion_corta_preview')->nullable();
            $table->text('descripcion_larga_preview')->nullable();
            $table->string('imagen_preview')->nullable();
            $table->decimal('precio_preview', 8, 2)->nullable();
            $table->string('estado', 20)->default('pendiente'); // pendiente | descartado | publicado
            $table->string('tour_id', 50)->nullable();
            $table->timestamp('fecha_importado');
            $table->timestamp('fecha_actualizado_catalogo');
            $table->timestamps();

            $table->unique(['api_conexion_id', 'external_id']);
            $table->foreign('api_conexion_id')->references('id')->on('api_conexiones')->onDelete('cascade');
            $table->foreign('tour_id')->references('id')->on('tours')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tours_importados');
    }
};
