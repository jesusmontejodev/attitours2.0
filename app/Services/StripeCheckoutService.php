<?php
/**
 * @file StripeCheckoutService.php
 * @description Lógica compartida para confirmar o liberar una Reserva a partir de una Checkout Session
 *              de Stripe. La usan tanto el webhook (fuente de verdad) como la página de éxito (fallback)
 *              y el comando de limpieza de reservas pendientes abandonadas.
 * @date 2026-07-27
 */

namespace App\Services;

use App\Mail\ReservaConfirmada;
use App\Models\Reserva;
use App\Models\TourFecha;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Stripe\Checkout\Session;

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

        DB::transaction(function () use ($reserva, $session) {
            $reserva->update([
                'estado' => 'Pagada',
                'stripe_payment_intent_id' => $session->payment_intent,
            ]);
        });

        try {
            Mail::to($reserva->correo_cliente)->send(new ReservaConfirmada($reserva));
        } catch (\Throwable $mailEx) {
            Log::warning('Error enviando correo de confirmación: ' . $mailEx->getMessage());
        }

        $this->notificarWebhookConfirmacion($reserva);

        return $reserva->fresh(['detalles.tour']);
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

            return "- {$nombre}: {$fecha}{$horario} ({$detalle->cantidad_personas} pax)";
        })->implode("\n");

        return "¡Hola {$reserva->nombre_cliente}! 🎉 Tu reserva con Atti Tours fue confirmada.\n\n"
            . "🎫 Ticket: {$reserva->ticket_codigo}\n\n"
            . "🧭 Tours reservados:\n{$tours}\n\n"
            . "💰 Total pagado: \${$reserva->precio_total_usd} USD\n\n"
            . "Presenta este código QR el día del tour:\n{$reserva->getQrImageUrl()}\n\n"
            . "¡Gracias por viajar con nosotros!";
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
                TourFecha::where('tour_id', $detalle->tour_id)
                    ->where('fecha', $detalle->fecha_seleccionada->format('Y-m-d'))
                    ->where('horario', $detalle->horario ?? '09:00')
                    ->decrement('cupo_reservado', $detalle->cantidad_personas);
            }

            $reserva->update(['estado' => 'Cancelada']);
        });
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
