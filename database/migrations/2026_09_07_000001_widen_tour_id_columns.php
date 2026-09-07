<?php
/**
 * @file 2026_09_07_000001_widen_tour_id_columns.php
 * @description El id de tours se genera con Str::slug(titulo_es) (hasta 150 caracteres) prefijado
 * con "tour_", pero la columna era varchar(50): con títulos largos el INSERT fallaba con
 * "Data too long for column 'id'". Se amplía tours.id y todas sus columnas FK tour_id a varchar(191).
 * MySQL no permite modificar una columna que participa en una foreign key (error 1833), así que
 * primero hay que tumbar las FKs de las tablas hijas, ampliar todas las columnas, y luego recrearlas.
 * @date 2026-09-07
 * @author Claude
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tablas hijas con FK tour_id -> tours.id, y su onDelete original.
     */
    private array $tablasHijas = [
        'tour_fechas' => 'cascade',
        'reserva_tours' => 'cascade',
        'tours_importados' => 'set null',
        'tour_cambios_precio_api' => 'cascade',
        'tour_api_notificaciones' => 'cascade',
        'tour_disponibilidad_syncs' => 'cascade',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach (array_keys($this->tablasHijas) as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->dropForeign(['tour_id']);
            });
        }

        Schema::table('tours', function (Blueprint $table) {
            $table->string('id', 191)->change();
        });

        foreach ($this->tablasHijas as $tabla => $onDelete) {
            Schema::table($tabla, function (Blueprint $table) use ($tabla) {
                $nullable = $tabla === 'tours_importados';
                $table->string('tour_id', 191)->nullable($nullable)->change();
            });
        }

        foreach ($this->tablasHijas as $tabla => $onDelete) {
            Schema::table($tabla, function (Blueprint $table) use ($onDelete) {
                $table->foreign('tour_id')->references('id')->on('tours')->onDelete($onDelete);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (array_keys($this->tablasHijas) as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->dropForeign(['tour_id']);
            });
        }

        Schema::table('tours', function (Blueprint $table) {
            $table->string('id', 50)->change();
        });

        foreach ($this->tablasHijas as $tabla => $onDelete) {
            Schema::table($tabla, function (Blueprint $table) use ($tabla) {
                $nullable = $tabla === 'tours_importados';
                $table->string('tour_id', 50)->nullable($nullable)->change();
            });
        }

        foreach ($this->tablasHijas as $tabla => $onDelete) {
            Schema::table($tabla, function (Blueprint $table) use ($onDelete) {
                $table->foreign('tour_id')->references('id')->on('tours')->onDelete($onDelete);
            });
        }
    }
};
