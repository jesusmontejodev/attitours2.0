<?php
/**
 * @file 2026_08_21_000000_create_mensajes_table.php
 * @description Crea la tabla de mensajería interna entre el cliente y el admin (actuando como
 *              proxy del proveedor). El proveedor nunca tiene acceso directo a esta tabla ni
 *              a sus datos de contacto reales se exponen al cliente en ningún punto del flujo.
 * @date 2026-08-21
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mensajes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('reserva_id');
            $table->unsignedBigInteger('reserva_tour_id')->nullable();
            $table->string('remitente_tipo', 30); // 'cliente' | 'admin_como_proveedor'
            $table->unsignedBigInteger('autor_user_id')->nullable();
            $table->text('cuerpo');
            $table->string('contacto_destino_usado')->nullable(); // auditoría interna, nunca visible al cliente
            $table->boolean('leido_por_cliente')->default(false);
            $table->boolean('leido_por_admin')->default(false);
            $table->timestamps();

            $table->foreign('reserva_id')->references('id')->on('reservas')->onDelete('cascade');
            $table->foreign('reserva_tour_id')->references('id')->on('reserva_tours')->onDelete('cascade');
            $table->foreign('autor_user_id')->references('id')->on('users')->onDelete('set null');
            $table->index(['reserva_id', 'created_at']);
            $table->index('leido_por_admin');
            $table->index('leido_por_cliente');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mensajes');
    }
};
