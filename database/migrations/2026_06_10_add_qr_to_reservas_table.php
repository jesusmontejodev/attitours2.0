<?php
/**
 * @file 2026_06_10_add_qr_to_reservas_table.php
 * @description Agrega columnas para el sistema de QR de asistencia: token único, estado de confirmación y proveedor que confirmó.
 * @date 2026-06-10
 * @author Antigravity
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservas', function (Blueprint $table) {
            $table->string('qr_token', 64)->nullable()->unique()->after('ticket_codigo')
                  ->comment('Token HMAC único para el QR de asistencia');
            $table->boolean('asistencia_confirmada')->default(false)->after('qr_token');
            $table->timestamp('asistencia_confirmada_at')->nullable()->after('asistencia_confirmada');
            $table->unsignedBigInteger('asistencia_confirmada_por')->nullable()->after('asistencia_confirmada_at')
                  ->comment('ID del proveedor que escaneó el QR');
            $table->foreign('asistencia_confirmada_por')->references('id')->on('proveedores')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('reservas', function (Blueprint $table) {
            $table->dropForeign(['asistencia_confirmada_por']);
            $table->dropColumn([
                'qr_token',
                'asistencia_confirmada',
                'asistencia_confirmada_at',
                'asistencia_confirmada_por',
            ]);
        });
    }
};
