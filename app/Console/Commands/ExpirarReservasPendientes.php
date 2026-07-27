<?php
/**
 * @file ExpirarReservasPendientes.php
 * @description Respaldo del webhook checkout.session.expired: cancela reservas Pendientes
 *              abandonadas (el usuario nunca completó el pago en Stripe) y libera sus cupos.
 * @date 2026-07-27
 */

namespace App\Console\Commands;

use App\Models\Reserva;
use App\Services\StripeCheckoutService;
use Illuminate\Console\Command;

class ExpirarReservasPendientes extends Command
{
    protected $signature = 'reservas:expirar-pendientes';

    protected $description = 'Cancela reservas Pendientes abandonadas y libera los cupos que tenían bloqueados';

    public function handle(StripeCheckoutService $stripeCheckout): int
    {
        $reservas = Reserva::with('detalles')
            ->where('estado', 'Pendiente')
            ->where('created_at', '<', now()->subMinutes(35))
            ->get();

        foreach ($reservas as $reserva) {
            $stripeCheckout->liberarReservaPendiente($reserva);
        }

        $this->info($reservas->count() . ' reserva(s) pendiente(s) expirada(s).');

        return self::SUCCESS;
    }
}
