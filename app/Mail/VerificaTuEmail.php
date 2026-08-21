<?php
/**
 * @file VerificaTuEmail.php
 * @description Mailable que envía el enlace de verificación de correo al crear una cuenta.
 * @date 2026-08-21
 * @author Antigravity
 */

namespace App\Mail;

use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VerificaTuEmail extends Mailable
{
    use SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly string $verificationUrl
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Confirma tu correo — Atti Tours',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.verificar_email',
            with: [
                'user' => $this->user,
                'verificationUrl' => $this->verificationUrl,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
