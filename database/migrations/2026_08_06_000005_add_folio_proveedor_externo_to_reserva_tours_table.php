<?php
/**
 * @file 2026_08_06_000005_add_folio_proveedor_externo_to_reserva_tours_table.php
 * @description Añade el folio de confirmación devuelto por la API externa (ej. "confirma" de Unique) al detalle de la reserva, para que sea visible sin ir a la tabla de auditoría tour_api_notificaciones.
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
        Schema::table('reserva_tours', function (Blueprint $table) {
            $table->string('folio_proveedor_externo')->nullable()->after('es_privado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reserva_tours', function (Blueprint $table) {
            $table->dropColumn('folio_proveedor_externo');
        });
    }
};
