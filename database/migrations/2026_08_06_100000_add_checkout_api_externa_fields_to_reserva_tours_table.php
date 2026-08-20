<?php
/**
 * @file 2026_08_06_100000_add_checkout_api_externa_fields_to_reserva_tours_table.php
 * @description Añade al detalle de reserva los campos que exige la API externa (Unique) al crear una reserva de su lado: desglose de pasajeros por categoría, idioma seleccionado y transportación (hotel/lobby/hora de pickup). Solo se usan cuando el tour reservado tiene origen = api_externa; para tours internos quedan siempre nulos.
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
            $table->integer('cantidad_adultos')->nullable()->after('cantidad_personas');
            $table->integer('cantidad_menores')->nullable()->after('cantidad_adultos');
            $table->integer('cantidad_infantes')->nullable()->after('cantidad_menores');
            $table->string('idioma_seleccionado', 10)->nullable()->after('folio_proveedor_externo');
            $table->string('hotel_nombre')->nullable()->after('idioma_seleccionado');
            $table->string('hotel_lobby')->nullable()->after('hotel_nombre');
            $table->string('pickup_horario', 10)->nullable()->after('hotel_lobby');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reserva_tours', function (Blueprint $table) {
            $table->dropColumn([
                'cantidad_adultos',
                'cantidad_menores',
                'cantidad_infantes',
                'idioma_seleccionado',
                'hotel_nombre',
                'hotel_lobby',
                'pickup_horario',
            ]);
        });
    }
};
