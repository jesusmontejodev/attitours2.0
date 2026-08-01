<?php
/**
 * @file StripeWebhookTest.php
 * @description Pruebas de Feature para validar el procesamiento seguro del webhook de Stripe.
 * @date 2026-07-31
 * @author Antigravity
 */

namespace Tests\Feature;

use App\Models\Reserva;
use App\Models\Tour;
use App\Models\TourFecha;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class StripeWebhookTest extends TestCase
{
    use RefreshDatabase;

    private string $webhookSecret = 'whsec_test_secret_key';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        // Configurar el secreto del webhook de Stripe para el entorno de pruebas
        config(['services.stripe.webhook_secret' => $this->webhookSecret]);

        // Evitar el envío real de correos durante las pruebas
        Mail::fake();
    }

    /**
     * Genera un header de firma de Stripe válido a partir de un payload y un secreto.
     */
    private function generateStripeSignature(string $payload, string $secret): string
    {
        $timestamp = time();
        $scheme = 'v1';
        $signedPayload = "{$timestamp}.{$payload}";
        $signature = hash_hmac('sha256', $signedPayload, $secret);

        return "t={$timestamp},{$scheme}={$signature}";
    }

    /**
     * Valida que el webhook sea rechazado con 400 si la firma de Stripe no coincide.
     */
    public function test_webhook_rejects_invalid_signature(): void
    {
        $payload = json_encode([
            'id' => 'evt_test_123',
            'type' => 'checkout.session.completed',
        ]);

        $response = $this->post(
            route('checkout.stripe.webhook'),
            json_decode($payload, true), // Se pasa como array en Laravel, pero el controlador leerá el contenido raw
            [
                'Stripe-Signature' => 'firma_invalida_totalmente'
            ]
        );

        $response->assertStatus(400);
        $response->assertSee('Firma inválida');
    }

    /**
     * Valida que el webhook procese correctamente el evento checkout.session.completed,
     * confirmando el pago, actualizando el estado de la reserva y creando la cuenta del usuario.
     */
    public function test_webhook_processes_checkout_session_completed_successfully(): void
    {
        $reserva = Reserva::create([
            'nombre_cliente' => 'Maria Lopez',
            'correo_cliente' => 'maria.lopez@example.com',
            'telefono_cliente' => '5551234',
            'precio_total_usd' => 150,
            'comision_total_usd' => 30,
            'estado' => 'Pendiente',
            'fecha_reserva' => now(),
            'ticket_codigo' => 'TKT-MARIATEST',
            'qr_token' => 'qr_token_maria',
            'stripe_session_id' => 'cs_test_completed_456'
        ]);

        $payloadData = [
            'id' => 'evt_test_completed',
            'object' => 'event',
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_test_completed_456',
                    'object' => 'checkout.session',
                    'payment_status' => 'paid',
                    'payment_intent' => 'pi_test_completed_456',
                    'metadata' => [
                        'reserva_id' => $reserva->id,
                        'temp_password' => 'passTempMaria123'
                    ]
                ]
            ]
        ];

        $payload = json_encode($payloadData);
        $signature = $this->generateStripeSignature($payload, $this->webhookSecret);

        // Realizamos la petición POST simulando el webhook de Stripe
        $response = $this->call(
            'POST',
            route('checkout.stripe.webhook'),
            [],
            [],
            [],
            [
                'HTTP_STRIPE_SIGNATURE' => $signature,
                'CONTENT_TYPE' => 'application/json'
            ],
            $payload
        );

        $response->assertStatus(200);
        $response->assertSee('OK');

        // Verificar que la reserva haya sido confirmada
        $reserva->refresh();
        $this->assertEquals('Pagada', $reserva->estado);
        $this->assertEquals('pi_test_completed_456', $reserva->stripe_payment_intent_id);

        // Verificar que se creó el usuario tipo Cliente en la BD
        $this->assertDatabaseHas('users', [
            'email' => 'maria.lopez@example.com',
            'name' => 'Maria Lopez',
            'tipo' => 'Cliente',
            'telefono' => '5551234'
        ]);

        // Verificar que el usuario recién creado fue asignado a la reserva
        $this->assertNotNull($reserva->user_id);
        $this->assertEquals('maria.lopez@example.com', $reserva->user->email);
    }

    /**
     * Valida que el webhook procese correctamente el evento checkout.session.expired,
     * liberando los cupos bloqueados y cancelando la reserva.
     */
    public function test_webhook_processes_checkout_session_expired_successfully(): void
    {
        $tour = Tour::first();
        $tourFecha = TourFecha::where('tour_id', $tour->id)->first();
        $tourFecha->update(['cupo_reservado' => 2]); // Inicializamos con 2 cupos reservados

        $reserva = Reserva::create([
            'nombre_cliente' => 'Usuario Expirado',
            'correo_cliente' => 'expirado@example.com',
            'telefono_cliente' => '0000000',
            'precio_total_usd' => 100,
            'comision_total_usd' => 20,
            'estado' => 'Pendiente',
            'fecha_reserva' => now(),
            'ticket_codigo' => 'TKT-EXPTEST',
            'qr_token' => 'qr_token_exp',
            'stripe_session_id' => 'cs_test_expired_789'
        ]);

        // Creamos un detalle de reserva asociado
        \App\Models\ReservaTour::create([
            'reserva_id' => $reserva->id,
            'tour_id' => $tour->id,
            'fecha_seleccionada' => $tourFecha->fecha->format('Y-m-d'),
            'horario' => $tourFecha->horario,
            'cantidad_personas' => 2,
            'precio_unitario_usd' => 50,
            'comision_usd' => 10
        ]);

        $payloadData = [
            'id' => 'evt_test_expired',
            'object' => 'event',
            'type' => 'checkout.session.expired',
            'data' => [
                'object' => [
                    'id' => 'cs_test_expired_789',
                    'object' => 'checkout.session',
                    'metadata' => [
                        'reserva_id' => $reserva->id
                    ]
                ]
            ]
        ];

        $payload = json_encode($payloadData);
        $signature = $this->generateStripeSignature($payload, $this->webhookSecret);

        $response = $this->call(
            'POST',
            route('checkout.stripe.webhook'),
            [],
            [],
            [],
            [
                'HTTP_STRIPE_SIGNATURE' => $signature,
                'CONTENT_TYPE' => 'application/json'
            ],
            $payload
        );

        $response->assertStatus(200);
        $response->assertSee('OK');

        // Verificar que la reserva se haya cancelado
        $reserva->refresh();
        $this->assertEquals('Cancelada', $reserva->estado);

        // Verificar que se hayan liberado los 2 cupos de la fecha del tour
        $tourFecha->refresh();
        $this->assertEquals(0, $tourFecha->cupo_reservado);
    }
}
