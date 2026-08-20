<?php
/**
 * @file 2026_08_06_000000_create_api_conexiones_table.php
 * @description Crea la tabla de conexiones a APIs externas de terceros (ej. Unique Reservaciones) para importar catálogos de tours.
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
        Schema::create('api_conexiones', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->unsignedBigInteger('proveedor_id')->nullable();
            $table->string('base_url');
            $table->text('usuario');
            $table->text('password');
            $table->string('uniqid_empresa');
            $table->string('modo', 20)->default('sandbox'); // sandbox | produccion
            $table->text('auth_token')->nullable();
            $table->timestamp('auth_token_expira_at')->nullable();
            $table->json('headers_extra')->nullable();
            $table->boolean('activa')->default(true);
            $table->timestamp('ultimo_sync_at')->nullable();
            $table->string('ultimo_sync_status')->nullable();
            $table->timestamps();

            $table->foreign('proveedor_id')->references('id')->on('proveedores')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_conexiones');
    }
};
