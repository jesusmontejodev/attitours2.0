<?php
/**
 * @file 2026_07_27_add_stripe_fields_to_reservas_table.php
 * @description Agrega los campos necesarios para vincular cada reserva con su Checkout Session de Stripe.
 * @date 2026-07-27
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservas', function (Blueprint $table) {
            $table->string('stripe_session_id', 100)->nullable()->unique()->after('qr_token');
            $table->string('stripe_payment_intent_id', 100)->nullable()->after('stripe_session_id');
        });
    }

    public function down(): void
    {
        Schema::table('reservas', function (Blueprint $table) {
            $table->dropColumn(['stripe_session_id', 'stripe_payment_intent_id']);
        });
    }
};
