<?php
/**
 * @file StripeCheckoutService.php
 * @description Lógica compartida para confirmar o liberar una Reserva a partir de una Checkout Session de Stripe. Modificado para soportar tours privados, cálculo de saldos pendientes en n8n/WhatsApp, y restablecimiento de disponibilidad al cancelar.
 * @date 2026-07-31
 * @author Antigravity
 */

namespace App\Services;

use App\Jobs\NotificarReservaApiExternaJob;
use App\Mail\ReservaConfirmada;
use App\Models\Reserva;
use App\Models\TourFecha;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Stripe\Checkout\Session;
use Stripe\Stripe;

class StripeCheckoutService
{
    /**
     * Marca la Reserva vinculada a la Session como Pagada y envía el correo de confirmación.
     * Idempotente: si ya estaba Pagada (p. ej. el webhook llegó antes que el fallback de /success),
     * no vuelve a procesarla ni reenvía el correo.
     */
    public function confirmarPago(Session $session): ?Reserva
    {
        $reserva = $this->resolverReserva($session);

        if (!$reserva || $reserva->estado === 'Pagada') {
            return $reserva;
        }

        if ($session->payment_status !== 'paid') {
            return null;
        }

        $tempPassword = $session->metadata->temp_password ?? null;

        DB::transaction(function () use ($reserva, $session, $tempPassword) {
            $userId = $reserva->user_id;

            if (!$userId) {
                // El usuario no estaba logueado. Buscamos por correo de la reserva.
                $email = strtolower(trim($reserva->correo_cliente));
                $user = User::where('email', $email)->first();

                if (!$user) {
                    // Crear usuario nuevo de tipo 'Cliente'
                    $finalPassword = $tempPassword ?: \Illuminate\Support\Str::random(10);
                    $user = User::create([
                        'name'      => $reserva->nombre_cliente,
                        'email'     => $email,
                        'password'  => Hash::make($finalPassword),
                        'tipo'      => 'Cliente',
                        'telefono'  => $reserva->telefono_cliente,
                    ]);
                }

                $userId = $user->id;
            }

            $reserva->update([
                'estado' => 'Pagada',
                'user_id' => $userId,
                'stripe_payment_intent_id' => $session->payment_intent,
            ]);
        });

        // Recargar relaciones y el usuario recién asociado
        $reserva->loadMissing('user');

        try {
            Mail::to($reserva->correo_cliente)->send(new ReservaConfirmada($reserva, $tempPassword));
        } catch (\Throwable $mailEx) {
            Log::warning('Error enviando correo de confirmación: ' . $mailEx->getMessage());
        }

        $this->notificarWebhookConfirmacion($reserva);
        $this->despacharNotificacionesApiExterna($reserva);

        return $reserva->fresh(['detalles.tour']);
    }

    /**
     * Encola una notificación a la API externa por cada tour de la reserva cuyo origen sea
     * api_externa (Unique). Los tours creados manualmente en la plataforma (origen = interno,
     * la mayoría) nunca disparan esto. No lanza excepciones: si falla el despacho a la cola,
     * no debe romper la confirmación del pago (el Job en sí ya tiene sus propios reintentos).
     */
    private function despacharNotificacionesApiExterna(Reserva $reserva): void
    {
        foreach ($reserva->detalles as $detalle) {
            if ($detalle->tour && $detalle->tour->esApiExterna()) {
                try {
                    NotificarReservaApiExternaJob::dispatch($detalle->id);
                } catch (\Throwable $dispatchEx) {
                    Log::warning("Error encolando notificación a API externa para el detalle {$detalle->id}: " . $dispatchEx->getMessage());
                }
            }
        }
    }

    /**
     * Notifica a n8n (u otro flujo externo) que una reserva fue pagada, para que desde ahí se
     * dispare el mensaje de confirmación (WhatsApp/SMS) al teléfono del cliente.
     * No lanza excepciones: si el webhook externo falla, no debe romper la confirmación del pago.
     */
    private function notificarWebhookConfirmacion(Reserva $reserva): void
    {
        $webhookUrl = config('services.n8n.confirmacion_webhook_url');

        if (!$webhookUrl) {
            return;
        }

        try {
            Http::timeout(5)->post($webhookUrl, [
                'reserva_id' => $reserva->id,
                'ticket_codigo' => $reserva->ticket_codigo,
                'estado' => $reserva->estado,
                'fecha_reserva' => $reserva->fecha_reserva?->toIso8601String(),
                'total_usd' => $reserva->precio_total_usd,
                'monto_pagado_online_usd' => $reserva->monto_pagado_online_usd,
                'monto_pendiente_destino_usd' => $reserva->monto_pendiente_destino_usd,
                'cliente' => [
                    'nombre' => $reserva->nombre_cliente,
                    'telefono' => $this->normalizarTelefono($reserva->telefono_cliente),
                    'correo' => $reserva->correo_cliente,
                ],
                'tours' => $reserva->detalles->map(fn ($detalle) => [
                    'nombre' => $detalle->tour->nombre ?? null,
                    'fecha' => $detalle->fecha_seleccionada->format('Y-m-d'),
                    'horario' => $detalle->horario,
                    'personas' => $detalle->cantidad_personas,
                    'es_privado' => (bool) $detalle->es_privado,
                ])->all(),
                'qr_url' => $reserva->getQrImageUrl(),
                'stripe_session_id' => $reserva->stripe_session_id,
                'mensaje' => $this->construirMensajeWhatsapp($reserva),
            ]);
        } catch (\Throwable $webhookEx) {
            Log::warning('Error notificando al webhook de n8n: ' . $webhookEx->getMessage());
        }
    }

    /**
     * Quita todo lo que no sea dígito (espacios, +, guiones, paréntesis).
     * La API de WhatsApp Business Cloud espera el número "limpio" (código de país + número, sin símbolos).
     */
    private function normalizarTelefono(?string $telefono): string
    {
        return preg_replace('/\D+/', '', $telefono ?? '');
    }

    /**
     * Arma el texto de confirmación ya listo para mandar por WhatsApp, con el detalle del recorrido.
     */
    private function construirMensajeWhatsapp(Reserva $reserva): string
    {
        $tours = $reserva->detalles->map(function ($detalle) {
            $nombre = $detalle->tour->nombre ?? 'Tour';
            $fecha = $detalle->fecha_seleccionada->locale('es')->translatedFormat('d M Y');
            $horario = $detalle->horario ? " a las {$detalle->horario}" : '';
            $modalidad = $detalle->es_privado ? " (Privado 🔒)" : " (Compartido 👥)";

            return "- {$nombre}: {$fecha}{$horario}{$modalidad} ({$detalle->cantidad_personas} pax)";
        })->implode("\n");

        $mensaje = "¡Hola {$reserva->nombre_cliente}! 🎉 Tu reserva con Atti Tours fue confirmada.\n\n"
            . "🎫 Ticket: {$reserva->ticket_codigo}\n\n"
            . "🧭 Tours reservados:\n{$tours}\n\n"
            . "💰 Total pagado hoy: \${$reserva->monto_pagado_online_usd} USD\n";

        if ($reserva->monto_pendiente_destino_usd > 0) {
            $mensaje .= "💵 Saldo restante a pagar el día del viaje: \${$reserva->monto_pendiente_destino_usd} USD\n\n";
        } else {
            $mensaje .= "\n";
        }

        $mensaje .= "Presenta este código QR el día del tour:\n{$reserva->getQrImageUrl()}\n\n"
            . "¡Gracias por viajar con nosotros!";

        return $mensaje;
    }

    /**
     * Cancela una Reserva vinculada a una Session expirada/abandonada y libera los cupos que tenía bloqueados.
     */
    public function liberarReserva(Session $session): void
    {
        $reserva = $this->resolverReserva($session);

        if ($reserva) {
            $this->liberarReservaPendiente($reserva);
        }
    }

    /**
     * Libera cupos y cancela una Reserva Pendiente (usado también por el comando de limpieza programado).
     */
    public function liberarReservaPendiente(Reserva $reserva): void
    {
        if ($reserva->estado !== 'Pendiente') {
            return;
        }

        DB::transaction(function () use ($reserva) {
            foreach ($reserva->detalles as $detalle) {
                if ($detalle->es_privado) {
                    // En tours privados, restauramos el cupo reservado de vuelta a 0
                    TourFecha::where('tour_id', $detalle->tour_id)
                        ->where('fecha', $detalle->fecha_seleccionada->format('Y-m-d'))
                        ->where('horario', $detalle->horario ?? '09:00')
                        ->update(['cupo_reservado' => 0]);
                } else {
                    TourFecha::where('tour_id', $detalle->tour_id)
                        ->where('fecha', $detalle->fecha_seleccionada->format('Y-m-d'))
                        ->where('horario', $detalle->horario ?? '09:00')
                        ->decrement('cupo_reservado', $detalle->cantidad_personas);
                }
            }

            $reserva->update(['estado' => 'Cancelada']);
        });
    }

    /**
     * Crea una Checkout Session dinámica en Stripe a partir de una reserva y sus items.
     */
    public function crearCheckoutSession(Reserva $reserva, array $lineItems, ?string $tempPassword): Session
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        return Session::create([
            'mode' => 'payment',
            'line_items' => $lineItems,
            'customer_email' => $reserva->correo_cliente,
            'client_reference_id' => (string) $reserva->id,
            'metadata' => [
                'reserva_id' => $reserva->id,
                'temp_password' => $tempPassword,
            ],
            'success_url' => route('checkout.success') . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('checkout.index'),
            'expires_at' => now()->addMinutes(30)->timestamp,
        ], [
            'idempotency_key' => 'reserva_session_' . $reserva->id,
        ]);
    }

    /**
     * Recupera una Checkout Session de Stripe a partir de su ID.
     */
    public function recuperarSession(string $sessionId): Session
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        return Session::retrieve($sessionId);
    }

    private function resolverReserva(Session $session): ?Reserva
    {
        $reservaId = $session->metadata->reserva_id ?? $session->client_reference_id ?? null;

        if (!$reservaId) {
            return null;
        }

        return Reserva::with('detalles.tour')->find($reservaId);
    }
}
