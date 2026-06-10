<?php
/**
 * @file 2026_06_09_202107_add_foto_url_to_proveedores_table.php
 * @description Agrega la columna foto_url para almacenar el logo o foto del proveedor.
 * @date 2026-06-09
 * @author Antigravity
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
        Schema::table('proveedores', function (Blueprint $table) {
            $table->string('foto_url')->nullable()->after('comision_porcentaje');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proveedores', function (Blueprint $table) {
            $table->dropColumn('foto_url');
        });
    }
};
