<?php
/**
 * @file NotificacionesReservaTest.php
 * @description Pruebas de las notificaciones que se disparan al confirmar el pago de una reserva:
 *              correo al cliente, correo a cada proveedor y WhatsApp (Cloud API de Meta). Crea sus
 *              propios datos (no depende del seeder) y simula Stripe, el correo y la Graph API.
 * @date 2026-10-12
 */

namespace Tests\Feature;

use App\Mail\NuevaReservaProveedor;
use App\Mail\ReservaConfirmada;
use App\Models\Proveedor;
use App\Models\Reserva;
use App\Models\ReservaTour;
use App\Models\Tour;
use App\Services\StripeCheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Stripe\Checkout\Session;
use Tests\TestCase;

class NotificacionesReservaTest extends TestCase
{
    use RefreshDatabase;

    private const GRAPH_URL = 'https://graph.facebook.com/v21.0/123456789/messages';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.whatsapp.token' => 'token_de_prueba',
            'services.whatsapp.phone_number_id' => '123456789',
            'services.whatsapp.template_name' => 'confirmacion_reserva_tour_v2',
            'services.whatsapp.template_language' => 'es_MX',
            'services.n8n.confirmacion_webhook_url' => null,
        ]);

        Mail::fake();
    }

    /**
     * Crea un proveedor, un tour y una reserva Pendiente con un detalle de ese tour.
     */
    private function crearReservaPendiente(string $telefono = '9981234567'): Reserva
    {
        $proveedor = Proveedor::create([
            'nombre_empresa' => 'Tours del Caribe',
            'descripcion' => 'Proveedor de prueba',
            'correo' => 'proveedor@example.com',
            'representante_nombre' => 'Ana Proveedora',
            'representante_telefono' => '9980000000',
        ]);

        $tour = Tour::create([
            'id' => 'tour_notificaciones_test',
            'proveedor_id' => $proveedor->id,
            'titulo' => ['es' => 'Snorkel en Cozumel', 'en' => 'Cozumel Snorkel'],
            'descripcion_corta' => ['es' => 'Corta'],
            'descripcion_larga' => ['es' => 'Larga'],
            'ubicacion' => 'COZUMEL',
            'pais' => 'México',
            'precio_base_usd' => 100,
            'duracion' => ['es' => '4 horas'],
            'imagen_destacada' => 'images/tour.jpg',
            'galeria' => [],
            'cupo_maximo' => 10,
            'tags' => [],
        ]);

        $reserva = Reserva::create([
            'nombre_cliente' => 'Carlos Pérez',
            'correo_cliente' => 'carlos@example.com',
            'telefono_cliente' => $telefono,
            'precio_total_usd' => 200,
            'monto_pagado_online_usd' => 200,
            'monto_pendiente_destino_usd' => 0,
            'comision_total_usd' => 30,
            'estado' => 'Pendiente',
            'fecha_reserva' => now(),
            'ticket_codigo' => 'TKT-NOTIF01',
            'qr_token' => 'qr_token_notif',
            'stripe_session_id' => 'cs_test_notif',
        ]);

        ReservaTour::create([
            'reserva_id' => $reserva->id,
            'tour_id' => $tour->id,
            'fecha_seleccionada' => now()->addDays(5)->format('Y-m-d'),
            'horario' => '09:00',
            'cantidad_personas' => 2,
            'precio_unitario_usd' => 100,
            'comision_usd' => 15,
        ]);

        return $reserva;
    }

    private function sessionPagada(Reserva $reserva): Session
    {
        return Session::constructFrom([
            'id' => 'cs_test_notif',
            'object' => 'checkout.session',
            'payment_status' => 'paid',
            'payment_intent' => 'pi_test_notif',
            'metadata' => ['reserva_id' => $reserva->id],
        ]);
    }

    public function test_confirmar_pago_envia_correo_al_cliente_y_al_proveedor(): void
    {
        Http::fake([self::GRAPH_URL => Http::response(['messages' => [['id' => 'wamid.test']]])]);
        $reserva = $this->crearReservaPendiente();

        app(StripeCheckoutService::class)->confirmarPago($this->sessionPagada($reserva));

        Mail::assertSent(ReservaConfirmada::class, function (ReservaConfirmada $mail) use ($reserva) {
            return $mail->hasTo('carlos@example.com') && $mail->reserva->is($reserva);
        });

        Mail::assertSent(NuevaReservaProveedor::class, function (NuevaReservaProveedor $mail) {
            return $mail->hasTo('proveedor@example.com') && $mail->detalles->count() === 1;
        });
    }

    /**
     * Mail::fake() no renderiza las vistas; si una plantilla Blade truena (variable inexistente,
     * relación nula) el correo real fallaría en producción. Aquí se fuerza el render.
     */
    public function test_los_correos_se_renderizan_sin_errores(): void
    {
        $reserva = $this->crearReservaPendiente();
        $reserva->update(['estado' => 'Pagada']);
        $reserva->load(['user', 'detalles.tour.proveedor']);
        $detalle = $reserva->detalles->first();

        $htmlCliente = (new ReservaConfirmada($reserva, 'PassTemporal123'))->render();
        $this->assertStringContainsString('TKT-NOTIF01', $htmlCliente);
        $this->assertStringContainsString('PassTemporal123', $htmlCliente);

        $htmlProveedor = (new NuevaReservaProveedor($reserva, $detalle->tour->proveedor, $reserva->detalles))->render();
        $this->assertStringContainsString('TKT-NOTIF01', $htmlProveedor);
    }

    public function test_confirmar_pago_envia_whatsapp_con_la_plantilla_correcta(): void
    {
        Http::fake([self::GRAPH_URL => Http::response(['messages' => [['id' => 'wamid.test']]])]);
        $reserva = $this->crearReservaPendiente('998 123 4567');

        app(StripeCheckoutService::class)->confirmarPago($this->sessionPagada($reserva));

        Http::assertSent(function (Request $request) {
            if ($request->url() !== self::GRAPH_URL) {
                return false;
            }

            $body = collect($request['template']['components'])->firstWhere('type', 'body')['parameters'];
            $parametros = collect($body)->pluck('text', 'parameter_name');

            return $request->hasHeader('Authorization', 'Bearer token_de_prueba')
                && $request['to'] === '529981234567'
                && $request['template']['name'] === 'confirmacion_reserva_tour_v2'
                && $request['template']['language']['code'] === 'es_MX'
                && $parametros['customer_name'] === 'Carlos Pérez'
                && $parametros['tour_name'] === 'Snorkel en Cozumel'
                && $parametros['tour_time'] === '09:00';
        });
    }

    public function test_si_whatsapp_falla_el_pago_se_confirma_y_queda_en_el_log(): void
    {
        Http::fake([self::GRAPH_URL => Http::response(['error' => ['message' => 'Invalid OAuth access token']], 401)]);
        Log::spy();
        $reserva = $this->crearReservaPendiente();

        app(StripeCheckoutService::class)->confirmarPago($this->sessionPagada($reserva));

        $this->assertEquals('Pagada', $reserva->fresh()->estado);
        Mail::assertSent(ReservaConfirmada::class);
        Log::shouldHaveReceived('warning')
            ->withArgs(fn ($mensaje) => str_contains($mensaje, 'WhatsApp') && str_contains($mensaje, '401'))
            ->once();
    }

    public function test_sin_credenciales_de_whatsapp_no_se_intenta_el_envio(): void
    {
        Http::fake();
        config(['services.whatsapp.token' => null]);
        $reserva = $this->crearReservaPendiente();

        app(StripeCheckoutService::class)->confirmarPago($this->sessionPagada($reserva));

        Http::assertNothingSent();
        Mail::assertSent(ReservaConfirmada::class);
    }

    public function test_una_reserva_ya_pagada_no_reenvia_notificaciones(): void
    {
        Http::fake();
        $reserva = $this->crearReservaPendiente();
        $reserva->update(['estado' => 'Pagada']);

        app(StripeCheckoutService::class)->confirmarPago($this->sessionPagada($reserva));

        Mail::assertNothingSent();
        Http::assertNothingSent();
    }
}
