<?php
/**
 * @file NuevaReservaProveedor.php
 * @description Mailable que avisa al proveedor que un cliente compró uno o más de sus tours.
 *              Solo incluye los detalles de la reserva que pertenecen a ese proveedor (una
 *              reserva puede mezclar tours de proveedores distintos) y los datos de contacto
 *              del cliente para coordinar el servicio.
 * @date 2026-09-28
 */

namespace App\Mail;

use App\Models\Proveedor;
use App\Models\Reserva;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class NuevaReservaProveedor extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param Reserva $reserva La reserva pagada.
     * @param Proveedor $proveedor Proveedor al que se notifica.
     * @param Collection $detalles Detalles (ReservaTour) de la reserva que pertenecen a este proveedor.
     */
    public function __construct(
        public readonly Reserva $reserva,
        public readonly Proveedor $proveedor,
        public readonly Collection $detalles
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '🧭 Nueva reserva en Attitour — ' . $this->reserva->ticket_codigo,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.nueva_reserva_proveedor',
            with: [
                'reserva'   => $this->reserva,
                'proveedor' => $this->proveedor,
                'detalles'  => $this->detalles,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
