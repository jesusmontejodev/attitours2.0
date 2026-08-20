<?php
/**
 * @file 2026_08_06_000003_create_tour_cambios_precio_api_table.php
 * @description Crea la cola de cambios de precio detectados en syncs posteriores para tours ya publicados provenientes de una API externa. Ningún cambio se aplica solo: requiere aprobación explícita del Admin.
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
        Schema::create('tour_cambios_precio_api', function (Blueprint $table) {
            $table->id();
            $table->string('tour_id', 50);
            $table->unsignedBigInteger('api_conexion_id');
            $table->decimal('precio_referencia_anterior', 8, 2)->nullable();
            $table->decimal('precio_referencia_nuevo', 8, 2);
            $table->decimal('precio_venta_actual', 8, 2)->nullable();
            $table->timestamp('detectado_at');
            $table->string('estado', 20)->default('pendiente'); // pendiente | aprobado | rechazado
            $table->unsignedBigInteger('resuelto_por_user_id')->nullable();
            $table->timestamp('resuelto_at')->nullable();
            $table->timestamps();

            $table->foreign('tour_id')->references('id')->on('tours')->onDelete('cascade');
            $table->foreign('api_conexion_id')->references('id')->on('api_conexiones')->onDelete('cascade');
            $table->foreign('resuelto_por_user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tour_cambios_precio_api');
    }
};
