<?php
/**
 * @file 2026_06_08_205144_create_atti_tours_tables.php
 * @description Crea las tablas necesarias para soportar el flujo de negocio de Atti Tours (proveedores, tours, fechas de salida, reservas y relaciones con usuarios).
 * @date 2026-06-08
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
        // 1. Tabla de Proveedores
        Schema::create('proveedores', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_empresa');
            $table->text('descripcion');
            $table->string('rfc')->nullable();
            $table->string('correo');
            $table->string('representante_nombre');
            $table->string('representante_telefono');
            $table->integer('comision_porcentaje')->default(15);
            $table->timestamps();
        });

        // 2. Tabla de Tours
        Schema::create('tours', function (Blueprint $table) {
            $table->string('id', 50)->primary(); // e.g. 'tour_cancun'
            $table->unsignedBigInteger('proveedor_id');
            $table->json('titulo'); // JSON {es: "...", en: "...", zh: "..."}
            $table->json('descripcion_corta'); // JSON
            $table->json('descripcion_larga'); // JSON
            $table->string('ubicacion');
            $table->string('pais');
            $table->decimal('precio_base_usd', 8, 2);
            $table->string('duracion');
            $table->string('imagen_destacada');
            $table->json('galeria'); // Array de URLs
            $table->integer('cupo_maximo');
            $table->json('tags'); // Array de strings
            $table->timestamps();

            $table->foreign('proveedor_id')->references('id')->on('proveedores')->onDelete('cascade');
        });

        // 3. Tabla de Fechas Habilitadas por Tour (Disponibilidad)
        Schema::create('tour_fechas', function (Blueprint $table) {
            $table->id();
            $table->string('tour_id', 50);
            $table->date('fecha');
            $table->integer('cupo_maximo');
            $table->integer('cupo_reservado')->default(0);
            $table->timestamps();

            $table->unique(['tour_id', 'fecha']);
            $table->foreign('tour_id')->references('id')->on('tours')->onDelete('cascade');
        });

        // 4. Modificar tabla de usuarios existente
        Schema::table('users', function (Blueprint $table) {
            $table->string('tipo', 20)->default('Cliente')->after('password'); // Cliente, PT (Proveedor), Admin
            $table->string('telefono', 30)->nullable()->after('tipo');
            $table->string('pais', 50)->nullable()->after('telefono');
            $table->unsignedBigInteger('proveedor_id')->nullable()->after('pais');

            $table->foreign('proveedor_id')->references('id')->on('proveedores')->onDelete('set null');
        });

        // 5. Tabla de Reservas (Cabecera)
        Schema::create('reservas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable(); // Opcional, si es cliente registrado
            $table->string('nombre_cliente');
            $table->string('correo_cliente');
            $table->string('telefono_cliente');
            $table->decimal('precio_total_usd', 10, 2);
            $table->decimal('comision_total_usd', 10, 2);
            $table->string('estado', 20)->default('Pagada'); // Pagada, Pendiente, Cancelada
            $table->dateTime('fecha_reserva');
            $table->string('ticket_codigo', 50)->unique();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });

        // 6. Tabla de Tours Reservados (Detalle de Reserva)
        Schema::create('reserva_tours', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('reserva_id');
            $table->string('tour_id', 50);
            $table->date('fecha_seleccionada');
            $table->integer('cantidad_personas');
            $table->decimal('precio_unitario_usd', 8, 2);
            $table->decimal('comision_usd', 8, 2);
            $table->timestamps();

            $table->foreign('reserva_id')->references('id')->on('reservas')->onDelete('cascade');
            $table->foreign('tour_id')->references('id')->on('tours')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reserva_tours');
        Schema::dropIfExists('reservas');

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'proveedor_id')) {
                $table->dropForeign(['proveedor_id']);
                $table->dropColumn('proveedor_id');
            }
            if (Schema::hasColumn('users', 'pais')) {
                $table->dropColumn('pais');
            }
            if (Schema::hasColumn('users', 'telefono')) {
                $table->dropColumn('telefono');
            }
            if (Schema::hasColumn('users', 'tipo')) {
                $table->dropColumn('tipo');
            }
        });

        Schema::dropIfExists('tour_fechas');
        Schema::dropIfExists('tours');
        Schema::dropIfExists('proveedores');
    }
};
