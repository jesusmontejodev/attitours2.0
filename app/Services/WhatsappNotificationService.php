<?php
/**
 * @file WhatsappNotificationService.php
 * @description Cliente delgado para la WhatsApp Cloud API (Meta). Envía la plantilla de
 *              confirmación de reserva (confirmacion_reserva_tour_v2, con imagen de header)
 *              al teléfono del cliente usando los datos de la Reserva recién pagada.
 * @date 2026-08-21
 * @author Antigravity
 */

namespace App\Services;

use App\Models\Reserva;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class WhatsappNotificationService
{
    /**
     * Envía el mensaje de plantilla de confirmación de reserva por WhatsApp al teléfono
     * del cliente. Devuelve la respuesta cruda de la Graph API.
     */
    public function enviarConfirmacionReserva(Reserva $reserva): array
    {
        $telefono = $this->normalizarTelefono($reserva->telefono_cliente);

        if (!$telefono) {
            throw new RuntimeException("La reserva {$reserva->id} no tiene un teléfono de cliente válido.");
        }

        $primerDetalle = $reserva->detalles->first();

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $telefono,
            'type' => 'template',
            'template' => [
                'name' => config('services.whatsapp.template_name'),
                'language' => ['code' => config('services.whatsapp.template_language')],
                'components' => [
                    [
                        'type' => 'header',
                        'parameters' => [
                            ['type' => 'image', 'image' => ['link' => asset('images/imagen-plantilla-whatsapp.png')]],
                        ],
                    ],
                    [
                        'type' => 'body',
                        'parameters' => [
                            ['type' => 'text', 'parameter_name' => 'customer_name', 'text' => $reserva->nombre_cliente],
                            ['type' => 'text', 'parameter_name' => 'tour_name', 'text' => $this->nombreTours($reserva)],
                            ['type' => 'text', 'parameter_name' => 'tour_date', 'text' => $this->fechaPrimerTour($primerDetalle)],
                            ['type' => 'text', 'parameter_name' => 'tour_time', 'text' => $primerDetalle?->horario ?? 'Por confirmar'],
                        ],
                    ],
                ],
            ],
        ];

        $phoneNumberId = config('services.whatsapp.phone_number_id');

        $response = Http::withToken(config('services.whatsapp.token'))
            ->timeout(15)
            ->post("https://graph.facebook.com/v21.0/{$phoneNumberId}/messages", $payload);

        if (!$response->successful()) {
            throw new RuntimeException("Error enviando WhatsApp de confirmación: HTTP {$response->status()} - " . $response->body());
        }

        return $response->json() ?? [];
    }

    /**
     * Nombres de los tours de la reserva, unidos por coma (la plantilla solo admite un
     * único texto para "tour_name").
     */
    private function nombreTours(Reserva $reserva): string
    {
        return $reserva->detalles
            ->map(fn ($detalle) => $detalle->tour->nombre ?? 'Tour')
            ->unique()
            ->implode(', ');
    }

    /**
     * Fecha del primer tour de la reserva, formateada en español (la plantilla solo
     * admite una única fecha).
     */
    private function fechaPrimerTour(?object $detalle): string
    {
        if (!$detalle || !$detalle->fecha_seleccionada) {
            return 'Por confirmar';
        }

        return \Carbon\Carbon::parse($detalle->fecha_seleccionada)->locale('es')->translatedFormat('d M Y');
    }

    /**
     * Limpia el teléfono y le antepone el código de país de México (52) si hace falta.
     * La WhatsApp Cloud API espera el número completo sin símbolos (código de país + número).
     */
    private function normalizarTelefono(?string $telefono): ?string
    {
        $limpio = preg_replace('/\D+/', '', $telefono ?? '');

        if (!$limpio) {
            return null;
        }

        if (strlen($limpio) === 10) {
            return '52' . $limpio;
        }

        return $limpio;
    }
}
