<?php
/**
 * @file ReservaConfirmada.php
 * @description Mailable que envía el correo de confirmación de reserva con QR único al cliente.
 *              Incluye los detalles del tour, código de ticket y el QR de asistencia.
 * @date 2026-06-10
 * @author Antigravity
 */

namespace App\Mail;

use App\Models\Reserva;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReservaConfirmada extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param Reserva $reserva La reserva confirmada con sus detalles cargados.
     */
    public function __construct(public readonly Reserva $reserva)
    {
    }

    /**
     * Asunto y remitente del correo.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '🎉 ¡Reserva Confirmada! Tu ticket para Atti Tours — ' . $this->reserva->ticket_codigo,
        );
    }

    /**
     * Plantilla de la vista del correo.
     */
    public function content(): Content
    {
        return new Content(
            view: 'mail.reserva_confirmada',
            with: [
                'reserva'   => $this->reserva,
                'qrUrl'     => $this->reserva->getQrImageUrl(220),
                'qrPayload' => $this->reserva->getQrPayload(),
            ],
        );
    }

    /**
     * Sin archivos adjuntos (QR se incluye como imagen en el HTML).
     */
    public function attachments(): array
    {
        return [];
    }
}
