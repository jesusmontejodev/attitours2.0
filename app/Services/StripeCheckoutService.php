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

        return $reserva->fresh(['detalles.tour']);
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
