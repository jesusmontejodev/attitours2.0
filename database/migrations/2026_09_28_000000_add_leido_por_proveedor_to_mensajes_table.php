<?php
/**
 * @file 2026_09_28_000000_add_leido_por_proveedor_to_mensajes_table.php
 * @description Permite que el proveedor responda directamente desde su panel los mensajes de
 *              las reservas de sus tours. Agrega un estado de lectura propio del proveedor,
 *              independiente del del admin, para el badge de no leídos de cada uno.
 * @date 2026-09-28
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mensajes', function (Blueprint $table) {
            $table->boolean('leido_por_proveedor')->default(false)->after('leido_por_admin');
            $table->index('leido_por_proveedor');
        });
    }

    public function down(): void
    {
        Schema::table('mensajes', function (Blueprint $table) {
            $table->dropIndex(['leido_por_proveedor']);
            $table->dropColumn('leido_por_proveedor');
        });
    }
};
