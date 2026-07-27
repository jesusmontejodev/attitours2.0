<?php
/**
 * @file CheckoutController.php
 * @description Controlador para el proceso de compra: arma el carrito como una Checkout Session
 *              dinámica de Stripe, confirma transacciones y genera tickets virtuales de viaje.
 * @date 2026-07-27
 * @author Antigravity
 */

namespace App\Http\Controllers;

use App\Models\Proveedor;
use App\Models\Reserva;
use App\Models\ReservaTour;
use App\Models\Tour;
use App\Models\TourFecha;
use App\Services\StripeCheckoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Stripe\Checkout\Session as StripeSession;
use Stripe\Stripe;

class CheckoutController extends Controller
{
    public function __construct(private readonly StripeCheckoutService $stripeCheckout)
    {
    }

    /**
     * Muestra la pantalla de checkout si hay items en el carrito.
     *
     * @return View|RedirectResponse
     */
    public function index()
    {
        $cart = session()->get('cart', []);

        if (empty($cart)) {
            return redirect()->route('catalog')->with('error', __('El carrito está vacío. Añade algún tour para continuar.'));
        }

        $total = 0;
        foreach ($cart as $item) {
            $total += $item['subtotal'];
        }

        return view('checkout', compact('cart', 'total'));
    }

    /**
     * Crea la Reserva en estado Pendiente (bloqueando cupos) y la Checkout Session dinámica de
     * Stripe a partir del carrito actual, y redirige al usuario a la página de pago de Stripe.
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function placeOrder(Request $request): RedirectResponse
    {
        $request->validate([
            'nombre' => 'required|string|max:100',
            'email' => 'required|email|max:100',
            'telefono' => 'required|string|max:30',
        ]);

        $cart = session()->get('cart', []);

        if (empty($cart)) {
            return redirect()->route('catalog')->with('error', __('El carrito está vacío.'));
        }

        try {
            [$reserva, $lineItems] = DB::transaction(function () use ($request, $cart) {
                $totalVenta = 0;
                $totalComision = 0;

                // 1. Validar disponibilidad de cupos de todos los tours antes de insertar nada
                $itemsAProcesar = [];
                foreach ($cart as $item) {
                    $tour = Tour::findOrFail($item['tour_id']);
                    $tourFecha = TourFecha::where('tour_id', $item['tour_id'])
                        ->where('fecha', $item['fecha'])
                        ->where('horario', $item['horario'] ?? '09:00')
                        ->lockForUpdate() // Bloqueo de fila para consistencia concurrente
                        ->first();

                    if (!$tourFecha || $tourFecha->cupo_disponible < $item['cantidad']) {
                        throw new \Exception(__('El tour ":nombre" ya no tiene cupos suficientes para la fecha :fecha.', [
                            'nombre' => $tour->nombre,
                            'fecha' => $item['fecha']
                        ]));
                    }

                    // Calcular comisiones
                    $proveedor = Proveedor::findOrFail($tour->proveedor_id);
                    $itemComision = $item['subtotal'] * ($proveedor->comision_porcentaje / 100);

                    $itemsAProcesar[] = [
                        'tour' => $tour,
                        'tourFecha' => $tourFecha,
                        'cantidad' => $item['cantidad'],
                        'horario' => $item['horario'] ?? null,
                        'precio_unitario' => $item['precio_unitario'],
                        'subtotal' => $item['subtotal'],
                        'comision' => $itemComision
                    ];

                    $totalVenta += $item['subtotal'];
                    $totalComision += $itemComision;
                }

                // 2. Crear cabecera de la Reserva en estado Pendiente (aún no se ha pagado)
                $ticketCodigo = 'TKT-' . Str::upper(Str::random(8));
                $reserva = Reserva::create([
                    'user_id'           => Auth::check() ? Auth::id() : null,
                    'nombre_cliente'    => $request->input('nombre'),
                    'correo_cliente'    => $request->input('email'),
                    'telefono_cliente'  => $request->input('telefono'),
                    'precio_total_usd'  => $totalVenta,
                    'comision_total_usd'=> $totalComision,
                    'estado'            => 'Pendiente',
                    'fecha_reserva'     => now(),
                    'ticket_codigo'     => $ticketCodigo,
                    'qr_token'          => Reserva::generarQrToken(0, $ticketCodigo), // id=0 temporal
                ]);

                // Actualizar el qr_token con el ID real ya conocido
                $reserva->qr_token = Reserva::generarQrToken($reserva->id, $ticketCodigo);
                $reserva->saveQuietly();

                // 3. Crear detalles, bloquear cupos y armar los line_items para Stripe
                $lineItems = [];
                foreach ($itemsAProcesar as $pItem) {
                    ReservaTour::create([
                        'reserva_id' => $reserva->id,
                        'tour_id' => $pItem['tour']->id,
                        'fecha_seleccionada' => $pItem['tourFecha']->fecha->format('Y-m-d'),
                        'horario' => $pItem['horario'],
                        'cantidad_personas' => $pItem['cantidad'],
                        'precio_unitario_usd' => $pItem['precio_unitario'],
                        'comision_usd' => $pItem['comision']
                    ]);

                    // Bloquear el cupo mientras el usuario paga en Stripe
                    $pItem['tourFecha']->increment('cupo_reservado', $pItem['cantidad']);

                    $lineItems[] = [
                        'price_data' => [
                            'currency' => 'usd',
                            'product_data' => ['name' => $pItem['tour']->nombre],
                            'unit_amount' => (int) round($pItem['precio_unitario'] * 100),
                        ],
                        'quantity' => $pItem['cantidad'],
                    ];
                }

                return [$reserva, $lineItems];
            });

            // 4. Crear la Checkout Session dinámica y redirigir al usuario a Stripe
            Stripe::setApiKey(config('services.stripe.secret'));

            $session = StripeSession::create([
                'mode' => 'payment',
                'line_items' => $lineItems,
                'customer_email' => $reserva->correo_cliente,
                'client_reference_id' => (string) $reserva->id,
                'metadata' => ['reserva_id' => $reserva->id],
                'success_url' => route('checkout.success') . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('checkout.index'),
                'expires_at' => now()->addMinutes(30)->timestamp,
            ], [
                'idempotency_key' => 'reserva_session_' . $reserva->id,
            ]);

            $reserva->update(['stripe_session_id' => $session->id]);

            session()->forget('cart');

            return redirect()->away($session->url);

        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Página de éxito tras volver de Stripe. El webhook es la fuente de verdad que confirma el
     * pago; aquí se hace una verificación de respaldo por si el webhook aún no ha llegado.
     *
     * @return View|RedirectResponse
     */
    public function success(Request $request)
    {
        $sessionId = $request->query('session_id');
        $reserva = null;

        if ($sessionId) {
            try {
                Stripe::setApiKey(config('services.stripe.secret'));
                $session = StripeSession::retrieve($sessionId);
                $reserva = $this->stripeCheckout->confirmarPago($session);
            } catch (\Exception $e) {
                Log::warning('No se pudo verificar la Checkout Session de Stripe: ' . $e->getMessage());
            }
        }

        if (!$reserva) {
            return redirect()->route('home');
        }

        $reserva->loadMissing('detalles.tour');

        return view('success', compact('reserva'));
    }

    /**
     * Simula el envío de notificaciones (correo o WhatsApp) y devuelve respuesta AJAX.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function sendSimulatedNotification(Request $request): JsonResponse
    {
        $request->validate([
            'channel' => 'required|in:email,whatsapp',
            'reserva_id' => 'required|exists:reservas,id'
        ]);

        $channel = $request->input('channel');

        // Simular un delay para dar sensación de procesamiento
        usleep(400000);

        if ($channel === 'email') {
            return response()->json([
                'success' => true,
                'message' => __('¡Correo enviado exitosamente!')
            ]);
        } else {
            return response()->json([
                'success' => true,
                'message' => __('Redirigiendo a WhatsApp...'),
                'redirect_url' => 'https://api.whatsapp.com/send?phone=5212345678&text=' . urlencode('Hola, aquí está tu ticket de viaje de Atti Tours.')
            ]);
        }
    }
}
