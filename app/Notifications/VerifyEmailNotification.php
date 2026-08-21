<?php
/**
 * @file VerifyEmailNotification.php
 * @description Notificación de verificación de correo que reemplaza el mail markdown por
 *              defecto de Laravel con nuestro Mailable de marca (App\Mail\VerificaTuEmail).
 * @date 2026-08-21
 * @author Antigravity
 */

namespace App\Notifications;

use App\Mail\VerificaTuEmail;
use Illuminate\Auth\Notifications\VerifyEmail as BaseVerifyEmail;
use Illuminate\Contracts\Mail\Mailable;

class VerifyEmailNotification extends BaseVerifyEmail
{
    public function toMail($notifiable): Mailable
    {
        $url = $this->verificationUrl($notifiable);

        return (new VerificaTuEmail($notifiable, $url))
            ->to($notifiable->getEmailForVerification());
    }
}
