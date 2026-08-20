<?php
/**
 * @file NotificarReservaApiExternaJob.php
 * @description Job en cola que notifica a la API externa (Unique) que se pagó una reserva de un tour con origen = api_externa, creando la reserva real de su lado (POST /create). Se despacha desde StripeCheckoutService::confirmarPago(). Reintenta automáticamente ante fallas de red o error del proveedor, sin bloquear la confirmación del pago del cliente.
 * @date 2026-08-07
 * @author Claude
 */

namespace App\Jobs;

use App\Models\ReservaTour;
use App\Services\TourApiNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class NotificarReservaApiExternaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public function __construct(public int $reservaTourId)
    {
    }

    /**
     * Backoff creciente: 1 min, 5 min, 15 min, 30 min, 1 hora.
     */
    public function backoff(): array
    {
        return [60, 300, 900, 1800, 3600];
    }

    public function handle(TourApiNotificationService $service): void
    {
        $reservaTour = ReservaTour::find($this->reservaTourId);
        if (!$reservaTour) {
            return;
        }

        $service->notificar($reservaTour);
    }
}
