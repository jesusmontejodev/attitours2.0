<?php
/**
 * @file 2026_07_27_create_qr_escaneos_table.php
 * @description Crea la tabla de auditoría que registra cada intento de escaneo de QR (exitoso o no),
 *              quién lo realizó y el resultado, independientemente de si terminó confirmando asistencia.
 * @date 2026-07-27
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qr_escaneos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('reserva_id')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->string('resultado', 30); // confirmed, already_confirmed, invalid, not_paid, wrong_provider
            $table->string('mensaje')->nullable();
            $table->string('token_escaneado', 100)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->foreign('reserva_id')->references('id')->on('reservas')->onDelete('set null');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qr_escaneos');
    }
};
