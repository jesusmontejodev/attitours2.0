<?php

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
            $table->decimal('punto_encuentro_lat', 10, 7)->nullable()->after('punto_encuentro');
            $table->decimal('punto_encuentro_lng', 10, 7)->nullable()->after('punto_encuentro_lat');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tours', function (Blueprint $table) {
            $table->dropColumn(['punto_encuentro_lat', 'punto_encuentro_lng']);
        });
    }
};
