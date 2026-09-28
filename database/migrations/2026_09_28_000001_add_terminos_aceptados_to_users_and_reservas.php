<?php
/**
 * @file 2026_09_28_000001_add_terminos_aceptados_to_users_and_reservas.php
 * @description Guarda cuándo y qué versión de los Términos y Condiciones / Aviso de Privacidad
 *              aceptó el usuario al registrarse y el cliente al reservar (evidencia del
 *              consentimiento). La versión viene de config('legal.version').
 * @date 2026-09-28
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['users', 'reservas'] as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->timestamp('terminos_aceptados_at')->nullable();
                $table->string('terminos_version', 30)->nullable();
            });
        }
    }

    public function down(): void
    {
        foreach (['users', 'reservas'] as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->dropColumn(['terminos_aceptados_at', 'terminos_version']);
            });
        }
    }
};
