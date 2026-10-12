<?php
/**
 * @file ProbarNotificaciones.php
 * @description Diagnóstico en vivo de las notificaciones de reserva (correo y WhatsApp). En el
 *              flujo real los errores solo quedan como warning en el log; este comando revisa la
 *              configuración, envía un correo y un WhatsApp reales usando la última reserva Pagada
 *              (con destinatario sustituido, sin guardar cambios) y muestra el error exacto.
 * @date 2026-10-12
 */

namespace App\Console\Commands;

use App\Mail\ReservaConfirmada;
use App\Models\Reserva;
use App\Services\WhatsappNotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class ProbarNotificaciones extends Command
{
    protected $signature = 'notificaciones:probar
                            {--email= : Correo que recibirá la confirmación de prueba}
                            {--telefono= : Teléfono que recibirá el WhatsApp de prueba (10 dígitos o con código de país)}';

    protected $description = 'Envía un correo y un WhatsApp reales de confirmación de reserva para verificar la configuración';

    public function handle(WhatsappNotificationService $whatsapp): int
    {
        $email = $this->option('email');
        $telefono = $this->option('telefono');

        if (!$email && !$telefono) {
            $this->error('Indica --email, --telefono o ambos.');

            return self::INVALID;
        }

        $this->revisarConfiguracion();

        $reserva = Reserva::with(['user', 'detalles.tour.proveedor'])
            ->where('estado', 'Pagada')
            ->latest('id')
            ->first();

        if (!$reserva) {
            $this->error('No hay reservas Pagadas para usar como muestra.');

            return self::FAILURE;
        }

        $this->line("Usando la reserva #{$reserva->id} ({$reserva->ticket_codigo}) como muestra.");

        $ok = true;

        if ($email) {
            $ok = $this->probarCorreo($reserva, $email) && $ok;
        }

        if ($telefono) {
            $ok = $this->probarWhatsapp($whatsapp, $reserva, $telefono) && $ok;
        }

        return $ok ? self::SUCCESS : self::FAILURE;
    }

    /**
     * Advierte de configuraciones que hacen que las notificaciones no salgan aunque no haya error.
     */
    private function revisarConfiguracion(): void
    {
        $mailer = config('mail.default');
        $this->line("Mailer: {$mailer} | Remitente: " . config('mail.from.address'));

        if (in_array($mailer, ['log', 'array'], true)) {
            $this->warn("  ⚠ MAIL_MAILER={$mailer}: los correos NO se envían, solo se escriben en el log.");
        }

        if (config('mail.from.address') === 'hello@example.com') {
            $this->warn('  ⚠ MAIL_FROM_ADDRESS sigue en el valor de ejemplo; muchos servidores SMTP lo rechazan.');
        }

        $appUrl = config('app.url');
        $this->line("APP_URL: {$appUrl}");

        if (str_contains($appUrl, 'localhost') || str_starts_with($appUrl, 'http://')) {
            $this->warn('  ⚠ La imagen del header de WhatsApp se arma con APP_URL; Meta necesita una URL pública HTTPS para descargarla.');
        }

        if (!config('services.whatsapp.token') || !config('services.whatsapp.phone_number_id')) {
            $this->warn('  ⚠ Falta WHATSAPP_TOKEN o WHATSAPP_PHONE_NUMBER_ID: el flujo real omite el WhatsApp sin avisar.');
        }

        if (app()->configurationIsCached()) {
            $this->line('  (config cacheada: si cambiaste el .env, ejecuta php artisan config:cache)');
        }
    }

    private function probarCorreo(Reserva $reserva, string $email): bool
    {
        $this->newLine();
        $this->line("Enviando correo de confirmación a {$email}...");

        try {
            Mail::to($email)->send(new ReservaConfirmada($reserva));
        } catch (\Throwable $e) {
            $this->error('  ✗ Falló el correo: ' . get_class($e) . ' - ' . $e->getMessage());

            return false;
        }

        $this->info('  ✓ El servidor de correo aceptó el mensaje. Revisa también la carpeta de spam.');

        return true;
    }

    private function probarWhatsapp(WhatsappNotificationService $whatsapp, Reserva $reserva, string $telefono): bool
    {
        $this->newLine();
        $this->line("Enviando WhatsApp (plantilla " . config('services.whatsapp.template_name') . ") a {$telefono}...");

        // Solo en memoria: nunca se guarda, la reserva real conserva su teléfono.
        $reserva->telefono_cliente = $telefono;

        try {
            $respuesta = $whatsapp->enviarConfirmacionReserva($reserva);
        } catch (\Throwable $e) {
            $this->error('  ✗ Falló el WhatsApp: ' . $e->getMessage());

            return false;
        }

        $mensajeId = $respuesta['messages'][0]['id'] ?? 'sin id';
        $this->info("  ✓ Meta aceptó el mensaje ({$mensajeId}).");
        $this->line('    Meta puede rechazarlo después (imagen inaccesible, plantilla pausada); si no llega, revisa el webhook de estados en Meta.');

        return true;
    }
}
