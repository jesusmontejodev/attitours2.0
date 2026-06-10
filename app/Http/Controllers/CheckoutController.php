<?php
/**
 * @file CheckoutController.php
 * @description Controlador para el proceso de compra, confirmación de transacciones y generación de tickets virtuales de viaje.
 * @date 2026-06-08
 * @author Antigravity
 */

namespace App\Http\Controllers;

use App\Mail\ReservaConfirmada;
use App\Models\Proveedor;
use App\Models\Reserva;
use App\Models\ReservaTour;
use App\Models\Tour;
use App\Models\TourFecha;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CheckoutController extends Controller
{
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
     * Procesa la compra e inserta los registros correspondientes en la base de datos de forma transaccional.
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
            'card_name' => 'required|string|max:100',
            'card_number' => 'required|string|min:16|max:19',
            'card_expiry' => 'required|string|max:5', // MM/YY
            'card_cvv' => 'required|string|min:3|max:4'
        ]);

        $cart = session()->get('cart', []);

        if (empty($cart)) {
            return redirect()->route('catalog')->with('error', __('El carrito está vacío.'));
        }

        try {
            $reservaId = DB::transaction(function () use ($request, $cart) {
                $totalVenta = 0;
                $totalComision = 0;
                
                // 1. Validar disponibilidad de cupos de todos los tours antes de insertar nada
                $itemsAProcesar = [];
                foreach ($cart as $key => $item) {
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

                // 2. Crear cabecera de la Reserva
                $ticketCodigo = 'TKT-' . Str::upper(Str::random(8));
                $reserva = Reserva::create([
                    'user_id'           => Auth::check() ? Auth::id() : null,
                    'nombre_cliente'    => $request->input('nombre'),
                    'correo_cliente'    => $request->input('email'),
                    'telefono_cliente'  => $request->input('telefono'),
                    'precio_total_usd'  => $totalVenta,
                    'comision_total_usd'=> $totalComision,
                    'estado'            => 'Pagada',
                    'fecha_reserva'     => now(),
                    'ticket_codigo'     => $ticketCodigo,
                    'qr_token'          => Reserva::generarQrToken(0, $ticketCodigo), // id=0 temporal
                ]);

                // Actualizar el qr_token con el ID real ya conocido
                $reserva->qr_token = Reserva::generarQrToken($reserva->id, $ticketCodigo);
                $reserva->saveQuietly();

                // 3. Crear detalles y actualizar cupos reservados en la base de datos
                foreach ($itemsAProcesar as $pItem) {
                    // Registrar el detalle
                    ReservaTour::create([
                        'reserva_id' => $reserva->id,
                        'tour_id' => $pItem['tour']->id,
                        'fecha_seleccionada' => $pItem['tourFecha']->fecha->format('Y-m-d'),
                        'horario' => $pItem['horario'],
                        'cantidad_personas' => $pItem['cantidad'],
                        'precio_unitario_usd' => $pItem['precio_unitario'],
                        'comision_usd' => $pItem['comision']
                    ]);

                    // Actualizar el cupo de la fecha
                    $pItem['tourFecha']->increment('cupo_reservado', $pItem['cantidad']);
                }

                return $reserva->id;
            });

            // Limpiar el carrito de la sesión
            session()->forget('cart');
            session()->put('last_reserva_id', $reservaId);

            // Enviar correo de confirmación con QR al cliente
            try {
                $reservaParaMail = Reserva::with(['detalles.tour'])->find($reservaId);
                if ($reservaParaMail) {
                    Mail::to($reservaParaMail->correo_cliente)
                        ->send(new ReservaConfirmada($reservaParaMail));
                }
            } catch (\Throwable $mailEx) {
                // El correo falla silenciosamente para no interrumpir el flujo de pago
                \Illuminate\Support\Facades\Log::warning('Error enviando correo de confirmación: ' . $mailEx->getMessage());
            }

            return redirect()->route('checkout.success');

        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Muestra la página de éxito tras una compra.
     *
     * @return View|RedirectResponse
     */
    public function success()
    {
        $reservaId = session()->get('last_reserva_id');

        if (!$reservaId) {
            return redirect()->route('home');
        }

        $reserva = Reserva::with(['detalles.tour'])->findOrFail($reservaId);

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
