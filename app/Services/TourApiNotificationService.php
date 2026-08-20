<?php
/**
 * @file TourApiNotificationService.php
 * @description Arma el payload de POST /create de Unique a partir de un detalle de reserva (ReservaTour) de un tour con origen = api_externa, lo envía, y registra el resultado en tour_api_notificaciones. Esta es la llamada que crea la reserva real dentro de Unique cuando un cliente de Attitour paga un tour importado.
 * @date 2026-08-07
 * @author Claude
 */

namespace App\Services;

use App\Models\ReservaTour;
use App\Models\TourApiNotificacion;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class TourApiNotificationService
{
    /**
     * Notifica la reserva a la API externa del tour. Lanza la excepción original si falla,
     * para que el Job que la invoca pueda reintentar según su política de backoff.
     */
    public function notificar(ReservaTour $reservaTour): void
    {
        $reservaTour->loadMissing(['tour.apiConexion', 'reserva']);
        $tour = $reservaTour->tour;
        $reserva = $reservaTour->reserva;

        if (!$tour || !$tour->esApiExterna() || !$tour->apiConexion || !$tour->api_locacion_id || !$tour->api_tour_id_externo) {
            return;
        }

        $registro = TourApiNotificacion::firstOrNew(['reserva_tour_id' => $reservaTour->id]);
        $registro->tour_id = $tour->id;
        $registro->reserva_id = $reserva->id;
        $registro->api_conexion_id = $tour->api_conexion_id;

        $payload = $this->construirPayload($tour, $reserva, $reservaTour);
        $registro->payload_enviado = $payload;
        $registro->intentos = ($registro->intentos ?? 0) + 1;
        $registro->estado = 'pendiente';
        $registro->save();

        try {
            $client = new UniqueApiClient($tour->apiConexion);
            $respuesta = $client->create($payload);

            $registro->respuesta_http_status = 200;
            $registro->respuesta_body = json_encode($respuesta);
            $registro->unique_reserva_id = isset($respuesta['id']) ? (string) $respuesta['id'] : null;
            $registro->unique_confirma = $respuesta['confirma'] ?? null;

            // Unique puede responder HTTP 200 con un cuerpo de error de negocio (ej. {"errors":"SOLD OUT"})
            // en vez de fallar a nivel HTTP — sin "confirma" no hay reserva real creada del otro lado,
            // así que se trata igual que una falla (con reintentos), no como un envío exitoso silencioso.
            if (empty($registro->unique_confirma)) {
                $registro->estado = 'fallido';
                $registro->save();

                throw new RuntimeException(
                    'Unique no confirmó la reserva: ' . ($respuesta['errors'] ?? json_encode($respuesta))
                );
            }

            $registro->estado = 'enviado';
            $registro->save();

            $reservaTour->update(['folio_proveedor_externo' => $registro->unique_confirma]);
        } catch (Throwable $e) {
            $registro->estado = 'fallido';
            $registro->respuesta_body = $registro->respuesta_body ?: $e->getMessage();
            $registro->save();

            Log::warning("TourApiNotificationService: fallo notificando reserva {$reserva->id} (detalle {$reservaTour->id}) a Unique: " . $e->getMessage());

            throw $e;
        }
    }

    /**
     * Arma el cuerpo exacto de POST /create según el contrato documentado en
     * docsIA/05-api-unique-referencia.md.
     */
    private function construirPayload($tour, $reserva, ReservaTour $reservaTour): array
    {
        $totalItem = (float) $reservaTour->precio_unitario_usd * (int) $reservaTour->cantidad_personas;

        $payload = [
            // Unique confirmó (2026-08-19, ver docsIA/04-preguntas-abiertas.md) que "disponibilidad":1
            // rechazando reservas con cupo real ("SOLD OUT") es un comportamiento exclusivo de su
            // Sandbox y no ocurre en producción. Por eso aquí SÍ se usa 1 en producción (Unique valida
            // su propio cupo en vivo, la opción más segura para no desincronizarse con Attitour) y se
            // usa 0 solo en Sandbox como workaround del bug confirmado de ese ambiente.
            'disponibilidad' => $tour->apiConexion->modo === 'produccion' ? 1 : 0,
            'fecha' => $reservaTour->fecha_seleccionada->format('Y-m-d'),
            'cliente' => $reserva->nombre_cliente,
            'pax' => $reservaTour->cantidad_personas,
            'paxn' => $reservaTour->cantidad_menores ?? 0,
            'pax_infantes' => $reservaTour->cantidad_infantes ?? 0,
            'locacion' => $tour->api_locacion_id,
            'servicio' => $tour->api_tour_id_externo,
            'horario' => $reservaTour->horario,
            'idioma' => $reservaTour->idioma_seleccionado ?: 'ESP',
            'detalles' => [
                'referencia' => $reserva->ticket_codigo,
                'email' => $reserva->correo_cliente,
                'total' => number_format($totalItem, 2, '.', '') . ' USD',
            ],
            'comentarios' => 'Reserva generada automáticamente desde Attitours (ticket ' . $reserva->ticket_codigo . ').',
        ];

        if ($reservaTour->hotel_nombre) {
            $payload['transportacion'] = [
                'hotel' => $reservaTour->hotel_nombre,
                'pick_up' => $reservaTour->pickup_horario,
            ];
        }

        return $payload;
    }
}
