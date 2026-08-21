<?php
/**
 * @file CheckoutController.php
 * @description Controlador para el proceso de compra: gestiona el carrito de compras, calcula los montos de anticipos (20%) y saldos en destino (80%) para tours privados, crea transacciones en Stripe Checkout y genera las reservas del cliente.
 * @date 2026-07-31
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
            'codigo_pais' => 'required|string|max:5|regex:/^\+[0-9]{1,4}$/',
            'telefono' => 'required|string|max:30',
        ]);

        $telefonoCompleto = trim($request->input('codigo_pais') . ' ' . $request->input('telefono'));

        $cart = session()->get('cart', []);

        if (empty($cart)) {
            return redirect()->route('catalog')->with('error', __('El carrito está vacío.'));
        }

        try {
            [$reserva, $lineItems] = DB::transaction(function () use ($request, $cart, $telefonoCompleto) {
                $totalVenta = 0;
                $totalComision = 0;
                $montoOnlineTotal = 0;
                $montoDestinoTotal = 0;

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

                    $esPrivado = (bool) ($item['es_privado'] ?? false);
                    $itemSubtotal = (float) $item['subtotal'];

                    if ($esPrivado) {
                        $porcentajeAnticipo = (int) ($tour->anticipo_porcentaje ?? 20);
                        $itemOnline = $itemSubtotal * ($porcentajeAnticipo / 100);
                        $itemDestino = $itemSubtotal * ((100 - $porcentajeAnticipo) / 100);
                    } else {
                        $itemOnline = $itemSubtotal;
                        $itemDestino = 0.00;
                    }

                    $itemsAProcesar[] = [
                        'tour' => $tour,
                        'tourFecha' => $tourFecha,
                        'cantidad' => $item['cantidad'],
                        'horario' => $item['horario'] ?? null,
                        'precio_unitario' => $item['precio_unitario'],
                        'subtotal' => $itemSubtotal,
                        'es_privado' => $esPrivado,
                        'monto_online' => $itemOnline,
                        'monto_destino' => $itemDestino,
                        'comision' => $itemComision,
                        // Solo presentes en items de tours con origen = api_externa (ver CartController::add).
                        'cantidad_adultos' => $item['cantidad_adultos'] ?? null,
                        'cantidad_menores' => $item['cantidad_menores'] ?? null,
                        'cantidad_infantes' => $item['cantidad_infantes'] ?? null,
                        'idioma_seleccionado' => $item['idioma_seleccionado'] ?? null,
                        'hotel_nombre' => $item['hotel_nombre'] ?? null,
                        'hotel_lobby' => $item['hotel_lobby'] ?? null,
                        'pickup_horario' => $item['pickup_horario'] ?? null,
                    ];

                    $totalVenta += $itemSubtotal;
                    $totalComision += $itemComision;
                    $montoOnlineTotal += $itemOnline;
                    $montoDestinoTotal += $itemDestino;
                }

                // 2. Crear cabecera de la Reserva en estado Pendiente (aún no se ha pagado)
                $ticketCodigo = 'TKT-' . Str::upper(Str::random(8));
                $reserva = Reserva::create([
                    'user_id'                     => Auth::check() ? Auth::id() : null,
                    'nombre_cliente'              => $request->input('nombre'),
                    'correo_cliente'              => $request->input('email'),
                    'telefono_cliente'            => $telefonoCompleto,
                    'precio_total_usd'            => $totalVenta,
                    'monto_pagado_online_usd'     => $montoOnlineTotal,
                    'monto_pendiente_destino_usd' => $montoDestinoTotal,
                    'comision_total_usd'          => $totalComision,
                    'estado'                      => 'Pendiente',
                    'fecha_reserva'               => now(),
                    'ticket_codigo'               => $ticketCodigo,
                    'qr_token'                    => Reserva::generarQrToken(0, $ticketCodigo), // id=0 temporal
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
                        'cantidad_adultos' => $pItem['cantidad_adultos'],
                        'cantidad_menores' => $pItem['cantidad_menores'],
                        'cantidad_infantes' => $pItem['cantidad_infantes'],
                        'es_privado' => $pItem['es_privado'],
                        'precio_unitario_usd' => $pItem['precio_unitario'],
                        'comision_usd' => $pItem['comision'],
                        'idioma_seleccionado' => $pItem['idioma_seleccionado'],
                        'hotel_nombre' => $pItem['hotel_nombre'],
                        'hotel_lobby' => $pItem['hotel_lobby'],
                        'pickup_horario' => $pItem['pickup_horario'],
                    ]);

                    // Bloquear el cupo mientras el usuario paga en Stripe
                    if ($pItem['es_privado']) {
                        // En tours privados el grupo bloquea el horario completo
                        $pItem['tourFecha']->update(['cupo_reservado' => $pItem['tourFecha']->cupo_maximo]);
                    } else {
                        $pItem['tourFecha']->increment('cupo_reservado', $pItem['cantidad']);
                    }

                    // En Stripe cobramos el monto online (si es privado es el anticipo_porcentaje, si es compartido es el 100%)
                    if ($pItem['es_privado']) {
                        $porcentajeAnticipo = (int) ($pItem['tour']->anticipo_porcentaje ?? 20);
                        $stripeUnitAmount = $pItem['precio_unitario'] * ($porcentajeAnticipo / 100);
                        $productName = "[Privado - Anticipo {$porcentajeAnticipo}%] " . $pItem['tour']->nombre;
                    } else {
                        $stripeUnitAmount = $pItem['precio_unitario'];
                        $productName = $pItem['tour']->nombre;
                    }

                    $lineItems[] = [
                        'price_data' => [
                            'currency' => 'usd',
                            'product_data' => ['name' => $productName],
                            'unit_amount' => (int) round($stripeUnitAmount * 100),
                        ],
                        'quantity' => $pItem['cantidad'],
                    ];
                }

                return [$reserva, $lineItems];
            });

            // Comprobar si el usuario existe o está autenticado para generar la contraseña temporal
            $tempPassword = null;
            if (!Auth::check()) {
                $email = strtolower(trim($reserva->correo_cliente));
                $userExists = \App\Models\User::where('email', $email)->exists();
                if (!$userExists) {
                    $tempPassword = Str::random(10);
                }
            }

            // 4. Crear la Checkout Session dinámica y redirigir al usuario a Stripe
            $session = $this->stripeCheckout->crearCheckoutSession($reserva, $lineItems, $tempPassword);

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
        $tempPassword = null;
        $userCreated = false;
        $userAssociated = false;

        if ($sessionId) {
            try {
                $session = $this->stripeCheckout->recuperarSession($sessionId);
                $reserva = $this->stripeCheckout->confirmarPago($session);

                if ($reserva) {
                    $tempPassword = $session->metadata->temp_password ?? null;
                    if ($tempPassword) {
                        $userCreated = true;

                        // Auto-iniciar sesión del nuevo usuario si no está logueado
                        if (!Auth::check() && $reserva->user_id) {
                            Auth::loginUsingId($reserva->user_id);
                            $request->session()->regenerate();
                        }
                    } else {
                        // Si no hay contraseña temporal pero el usuario no estaba logueado y ahora la reserva
                        // tiene un user_id asociado en la base de datos (usuario ya existía previamente)
                        if (!Auth::check() && $reserva->user_id) {
                            $userAssociated = true;
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::warning('No se pudo verificar la Checkout Session de Stripe: ' . $e->getMessage());
            }
        }

        if (!$reserva) {
            return redirect()->route('home');
        }

        $reserva->loadMissing('detalles.tour');

        return view('success', compact('reserva', 'tempPassword', 'userCreated', 'userAssociated'));
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
