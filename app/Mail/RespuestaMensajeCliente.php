<?php
/**
 * @file RespuestaMensajeCliente.php
 * @description Mailable que avisa al cliente de una nueva respuesta en el chat de su reserva
 *              (el admin respondiendo en nombre del proveedor). El correo nunca incluye el
 *              contacto real del proveedor, solo enlaza de vuelta a "Mi Cuenta".
 * @date 2026-08-21
 * @author Antigravity
 */

namespace App\Mail;

use App\Models\Mensaje;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RespuestaMensajeCliente extends Mailable
{
    use SerializesModels;

    public function __construct(
        public readonly Mensaje $mensaje
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Tienes una respuesta nueva sobre tu reserva ' . $this->mensaje->reserva->ticket_codigo . ' — Attitour',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.respuesta_mensaje',
            with: [
                'mensaje' => $this->mensaje,
                'reserva' => $this->mensaje->reserva,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
