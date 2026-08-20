<?php
/**
 * @file 2026_08_06_000004_create_tour_api_notificaciones_table.php
 * @description Crea la tabla de auditoría de las reservas creadas en la API externa (ej. POST /create de Unique) cuando se paga una reserva de un tour de origen api_externa. Registra el folio de confirmación devuelto y soporta reintentos.
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
        Schema::create('tour_api_notificaciones', function (Blueprint $table) {
            $table->id();
            $table->string('tour_id', 50);
            $table->unsignedBigInteger('reserva_id');
            $table->unsignedBigInteger('reserva_tour_id');
            $table->unsignedBigInteger('api_conexion_id');
            $table->json('payload_enviado')->nullable();
            $table->integer('respuesta_http_status')->nullable();
            $table->text('respuesta_body')->nullable();
            $table->string('unique_reserva_id')->nullable();
            $table->string('unique_confirma')->nullable();
            $table->string('estado', 20)->default('pendiente'); // pendiente | enviado | fallido
            $table->integer('intentos')->default(0);
            $table->timestamp('proximo_intento_at')->nullable();
            $table->timestamps();

            $table->foreign('tour_id')->references('id')->on('tours')->onDelete('cascade');
            $table->foreign('reserva_id')->references('id')->on('reservas')->onDelete('cascade');
            $table->foreign('reserva_tour_id')->references('id')->on('reserva_tours')->onDelete('cascade');
            $table->foreign('api_conexion_id')->references('id')->on('api_conexiones')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tour_api_notificaciones');
    }
};
