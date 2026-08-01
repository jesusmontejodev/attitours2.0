<?php
/**
 * @file CheckoutTest.php
 * @description Pruebas de Feature para verificar el flujo de compra y checkout con Stripe de forma segura y aislada.
 * @date 2026-07-31
 * @author Antigravity
 */

namespace Tests\Feature;

use App\Models\Reserva;
use App\Models\Tour;
use App\Models\TourFecha;
use App\Models\User;
use App\Services\StripeCheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Poblar base de datos con los seeders predefinidos (Tours, Fechas, Proveedores, etc.)
        $this->seed();
    }

    /**
     * Valida que si el carrito está vacío, se redirija al catálogo con error.
     */
    public function test_index_redirects_to_catalog_if_cart_is_empty(): void
    {
        $response = $this->get(route('checkout.index'));

        $response->assertRedirect(route('catalog'));
        $response->assertSessionHas('error');
    }

    /**
     * Valida que si el carrito tiene elementos, se renderice la pantalla de checkout.
     */
    public function test_index_shows_checkout_page_if_cart_has_items(): void
    {
        $tour = Tour::first();
        $tourFecha = TourFecha::where('tour_id', $tour->id)->first();

        session()->put('cart', [
            'item_1' => [
                'tour_id' => $tour->id,
                'nombre' => $tour->nombre,
                'imagen' => 'https://example.com/imagen.jpg',
                'fecha' => $tourFecha->fecha->format('Y-m-d'),
                'horario' => $tourFecha->horario,
                'cantidad' => 1,
                'precio_unitario' => $tour->precio_base_usd,
                'subtotal' => $tour->precio_base_usd,
            ]
        ]);

        $response = $this->get(route('checkout.index'));

        $response->assertStatus(200);
        $response->assertViewIs('checkout');
        $response->assertViewHas('cart');
        $response->assertViewHas('total', $tour->precio_base_usd);
    }

    /**
     * Valida la validación de campos obligatorios al realizar la orden.
     */
    public function test_place_order_validates_required_fields(): void
    {
        $response = $this->post(route('checkout.pay'), []);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['nombre', 'email', 'telefono']);
    }

    /**
     * Valida que al enviar datos correctos se cree la reserva en base de datos, 
     * se bloquee el cupo, se limpie el carrito y se redirija al checkout de Stripe.
     */
    public function test_place_order_creates_pending_reserva_and_redirects_to_stripe(): void
    {
        $tour = Tour::first();
        $tourFecha = TourFecha::where('tour_id', $tour->id)->first();
        $cupoReservadoAntes = $tourFecha->cupo_reservado;

        session()->put('cart', [
            'item_1' => [
                'tour_id' => $tour->id,
                'nombre' => $tour->nombre,
                'imagen' => 'https://example.com/imagen.jpg',
                'fecha' => $tourFecha->fecha->format('Y-m-d'),
                'horario' => $tourFecha->horario,
                'cantidad' => 2,
                'precio_unitario' => 100,
                'subtotal' => 200,
            ]
        ]);

        // Instanciar Session de Stripe de forma nativa usando constructFrom
        $sessionMock = \Stripe\Checkout\Session::constructFrom([
            'id' => 'cs_test_mock_123',
            'url' => 'https://checkout.stripe.com/pay/cs_test_mock_123'
        ]);

        $this->mock(StripeCheckoutService::class, function ($mock) use ($sessionMock) {
            $mock->shouldReceive('crearCheckoutSession')
                ->once()
                ->andReturn($sessionMock);
        });

        $response = $this->post(route('checkout.pay'), [
            'nombre' => 'Juan Perez',
            'email' => 'juan.perez@example.com',
            'telefono' => '+521234567890'
        ]);

        // Verificar redirección al URL de Stripe mockeado
        $response->assertRedirect('https://checkout.stripe.com/pay/cs_test_mock_123');

        // Verificar que el carrito se limpió de la sesión
        $this->assertEmpty(session('cart', []));

        // Verificar que la reserva se creó en la BD con estado Pendiente y con los detalles correctos
        $reserva = Reserva::where('correo_cliente', 'juan.perez@example.com')->first();
        $this->assertNotNull($reserva);
        $this->assertEquals('Pendiente', $reserva->estado);
        $this->assertEquals('cs_test_mock_123', $reserva->stripe_session_id);

        // Verificar que el cupo reservado de la fecha aumentó en 2
        $tourFecha->refresh();
        $this->assertEquals($cupoReservadoAntes + 2, $tourFecha->cupo_reservado);
    }

    /**
     * Valida el retorno exitoso de Stripe. Simula que la confirmación de pago del servicio funciona
     * correctamente y que se inicia sesión de manera automática si se generó una cuenta nueva.
     */
    public function test_success_page_confirms_payment_and_auto_logins_new_user(): void
    {
        $reserva = Reserva::create([
            'nombre_cliente' => 'Cliente Nuevo',
            'correo_cliente' => 'nuevo.cliente@example.com',
            'telefono_cliente' => '12345678',
            'precio_total_usd' => 100,
            'comision_total_usd' => 20,
            'estado' => 'Pendiente',
            'fecha_reserva' => now(),
            'ticket_codigo' => 'TKT-NEWTEST',
            'qr_token' => 'qr_token_test',
            'stripe_session_id' => 'cs_test_success_123'
        ]);

        $user = User::create([
            'name' => 'Cliente Nuevo',
            'email' => 'nuevo.cliente@example.com',
            'password' => bcrypt('password123'),
            'tipo' => 'Cliente',
        ]);

        // Asociar el usuario en la reserva como si confirmarPago lo hubiera hecho
        $reserva->update(['user_id' => $user->id, 'estado' => 'Pagada']);

        // Instanciar Session de Stripe de forma nativa usando constructFrom
        $sessionMock = \Stripe\Checkout\Session::constructFrom([
            'id' => 'cs_test_success_123',
            'metadata' => [
                'reserva_id' => $reserva->id,
                'temp_password' => 'tempPassword123'
            ]
        ]);

        $this->mock(StripeCheckoutService::class, function ($mock) use ($sessionMock, $reserva) {
            $mock->shouldReceive('recuperarSession')
                ->once()
                ->with('cs_test_success_123')
                ->andReturn($sessionMock);

            $mock->shouldReceive('confirmarPago')
                ->once()
                ->with($sessionMock)
                ->andReturn($reserva);
        });

        $response = $this->get(route('checkout.success') . '?session_id=cs_test_success_123');

        $response->assertStatus(200);
        $response->assertViewIs('success');
        $response->assertViewHas('reserva');
        $response->assertViewHas('tempPassword', 'tempPassword123');
        $response->assertViewHas('userCreated', true);

        // Verificar que el usuario se autenticó automáticamente
        $this->assertTrue(auth()->check());
        $this->assertEquals($user->id, auth()->id());
    }
}
