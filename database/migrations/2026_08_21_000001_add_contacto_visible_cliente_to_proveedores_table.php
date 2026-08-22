<?php
/**
 * @file 2026_08_21_000001_add_contacto_visible_cliente_to_proveedores_table.php
 * @description Agrega una etiqueta de presentación pública del proveedor (ej. "Operador local
 *              certificado"), mostrada al cliente en el chat en vez de su contacto real
 *              (representante_telefono / correo), que permanece oculto en todo momento.
 * @date 2026-08-21
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proveedores', function (Blueprint $table) {
            $table->string('contacto_visible_cliente')->nullable()->after('representante_telefono');
        });
    }

    public function down(): void
    {
        Schema::table('proveedores', function (Blueprint $table) {
            $table->dropColumn('contacto_visible_cliente');
        });
    }
};
