<?php
/**
 * @file 2026_08_06_000002_add_api_fields_to_tours_table.php
 * @description Añade a la tabla de tours los campos necesarios para marcar un tour como proveniente de una API externa (origen, referencia al servicio/locación en el origen, y precio de referencia informativo).
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
        Schema::table('tours', function (Blueprint $table) {
            $table->string('origen', 20)->default('interno')->after('id'); // interno | api_externa
            $table->unsignedBigInteger('api_conexion_id')->nullable()->after('origen');
            $table->string('api_tour_id_externo')->nullable()->after('api_conexion_id');
            $table->string('api_locacion_id')->nullable()->after('api_tour_id_externo');
            $table->decimal('precio_api_referencia_usd', 8, 2)->nullable()->after('api_locacion_id');
            $table->timestamp('precio_api_actualizado_at')->nullable()->after('precio_api_referencia_usd');
            $table->json('api_metadata')->nullable()->after('precio_api_actualizado_at');

            $table->foreign('api_conexion_id')->references('id')->on('api_conexiones')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tours', function (Blueprint $table) {
            $table->dropForeign(['api_conexion_id']);
            $table->dropColumn([
                'origen',
                'api_conexion_id',
                'api_tour_id_externo',
                'api_locacion_id',
                'precio_api_referencia_usd',
                'precio_api_actualizado_at',
                'api_metadata',
            ]);
        });
    }
};
