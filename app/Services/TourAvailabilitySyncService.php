<?php
/**
 * @file TourAvailabilitySyncService.php
 * @description Consulta la disponibilidad en tiempo real de Unique (endpoint /sold_out) para
 *              tours individuales que tengan "Activar conexión automática de disponibilidad"
 *              prendido (Tour::sync_calendario_activo). Se usa como tope adicional sobre el cupo
 *              local: el cupo mostrado al cliente (y validado al momento de comprar, en
 *              CheckoutController::placeOrder) es el mínimo entre lo que Unique reporta disponible
 *              y lo que Attitour ya tiene reservado localmente, para nunca sobrevender en ninguno
 *              de los dos sistemas.
 * @date 2026-08-22
 * @author Antigravity
 */

namespace App\Services;

use App\Models\Tour;
use App\Models\TourDisponibilidadSync;
use App\Models\TourFecha;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class TourAvailabilitySyncService
{
    /**
     * Cuánto tiempo se cachea la disponibilidad externa por tour+fecha+horario, para no golpear
     * la API de Unique en cada carga de página cuando varios visitantes consultan la misma fecha.
     */
    private const CACHE_TTL_SEGUNDOS = 120;

    /**
     * Cuánto tiempo se espera entre sincronizaciones automáticas del calendario completo de un
     * mismo tour disparadas por visitas a su ficha pública, para no golpear la API de Unique ni
     * llenar el historial de sincronizaciones con una fila por cada visitante.
     */
    private const CACHE_TTL_SYNC_CALENDARIO_SEGUNDOS = 300;

    /**
     * Cuántos días hacia adelante se trae el calendario en cada sincronización.
     */
    private const DIAS_SYNC_ADELANTE = 60;

    /**
     * Devuelve el cupo disponible que reporta Unique para este tour/fecha/horario, o null si
     * el tour no tiene el sync activo, no hay dato para ese horario, o la consulta falla
     * (en cuyo caso se debe seguir usando el cupo local, nunca bloquear la disponibilidad por esto).
     */
    public function disponibilidadExterna(Tour $tour, string $fecha, ?string $horario): ?int
    {
        if (!$tour->esApiExterna() || !$tour->sync_calendario_activo) {
            return null;
        }

        $conexion = $tour->apiConexion;

        if (!$conexion || !$tour->api_locacion_id || !$tour->api_tour_id_externo) {
            return null;
        }

        $cacheKey = "disponibilidad_externa:{$tour->id}:{$fecha}:" . ($horario ?? '_');

        return Cache::remember($cacheKey, self::CACHE_TTL_SEGUNDOS, function () use ($tour, $conexion, $fecha, $horario) {
            try {
                $client = new UniqueApiClient($conexion);
                $items = $client->soldOut($fecha, $tour->api_locacion_id, $tour->api_tour_id_externo, $horario);

                foreach ($items as $item) {
                    if ($horario !== null && ($item['horario'] ?? null) !== $horario) {
                        continue;
                    }
                    if (isset($item['disponibles'])) {
                        return (int) $item['disponibles'];
                    }
                }

                return null;
            } catch (Throwable $e) {
                Log::warning("TourAvailabilitySyncService: error consultando disponibilidad externa del tour {$tour->id} ({$fecha} {$horario}): " . $e->getMessage());

                return null;
            }
        });
    }

    /**
     * Sincroniza el calendario completo (próximos DIAS_SYNC_ADELANTE días) de un tour con sync
     * activo contra su API externa: crea/actualiza los TourFecha locales que Unique reporta con
     * cupo, y deshabilita (borra) los que Unique ya no ofrece y no tienen reservas activas — las
     * que sí tienen reservas se conservan intactas. Registra el resultado en el historial
     * (tour_disponibilidad_syncs) para que el Admin pueda auditar cuándo y qué trajo cada corrida.
     *
     * El cupo_maximo local que se guarda no es la fuente de verdad de disponibilidad — solo define
     * qué fechas existen como opción de compra. La disponibilidad real que se muestra y se valida
     * en cada compra siempre se vuelve a consultar en vivo vía disponibilidadExterna() (con su propio
     * caché corto), así que un dato desactualizado aquí nunca puede causar sobreventa.
     */
    public function sincronizarCalendario(Tour $tour, string $origen = 'manual'): TourDisponibilidadSync
    {
        if (!$tour->esApiExterna() || !$tour->sync_calendario_activo) {
            throw new RuntimeException('Este tour no tiene la sincronización de calendario activa.');
        }

        $conexion = $tour->apiConexion;
        if (!$conexion || !$tour->api_locacion_id || !$tour->api_tour_id_externo) {
            throw new RuntimeException('Este tour no tiene una conexión API válida configurada.');
        }

        $fechaInicio = now()->format('Y-m-d');
        $fechaFin = now()->addDays(self::DIAS_SYNC_ADELANTE)->format('Y-m-d');

        try {
            $client = new UniqueApiClient($conexion);
            $calendario = $client->soldOut($fechaInicio, $tour->api_locacion_id, $tour->api_tour_id_externo, null, $fechaFin);
        } catch (Throwable $e) {
            Log::warning("TourAvailabilitySyncService: error sincronizando calendario del tour {$tour->id}: " . $e->getMessage());

            return TourDisponibilidadSync::create([
                'tour_id' => $tour->id,
                'api_conexion_id' => $conexion->id,
                'origen' => $origen,
                'estado' => 'fallido',
                'mensaje_error' => Str::limit($e->getMessage(), 500),
            ]);
        }

        // Los tours "Sólo Privado" siempre sincronizan como modalidad privada (exclusiva) —
        // ver TourFecha::estaBloqueadoPorReservaPrivada. Unique no distingue compartido/privado
        // en /sold_out, así que un tour "Mixto" sincronizado por API solo puede traer un lado
        // (el compartido); el privado de un tour mixto sigue gestionándose a mano desde el Admin.
        $esPrivado = $tour->tipo_modalidad === 'privado';

        $fechasActualizadas = 0;
        $clavesEnCalendario = [];

        foreach ($calendario as $fechaStr => $horarios) {
            if (!is_array($horarios)) {
                continue;
            }

            foreach ($horarios as $item) {
                $horario = $item['horario'] ?? null;
                $disponibles = $item['disponibles'] ?? null;
                if (!$horario || $disponibles === null) {
                    continue;
                }

                $clavesEnCalendario[$fechaStr . '|' . $horario] = true;

                $cupoReservado = TourFecha::where('tour_id', $tour->id)
                    ->where('fecha', $fechaStr)
                    ->where('horario', $horario)
                    ->where('es_privado', $esPrivado)
                    ->value('cupo_reservado') ?? 0;

                TourFecha::updateOrCreate(
                    ['tour_id' => $tour->id, 'fecha' => $fechaStr, 'horario' => $horario, 'es_privado' => $esPrivado],
                    ['cupo_maximo' => $cupoReservado + max(0, (int) $disponibles)]
                );
                $fechasActualizadas++;
            }
        }

        $fechasDeshabilitadas = 0;
        $localesEnVentana = TourFecha::where('tour_id', $tour->id)
            ->where('es_privado', $esPrivado)
            ->whereBetween('fecha', [$fechaInicio, $fechaFin])
            ->get();

        foreach ($localesEnVentana as $local) {
            $clave = $local->fecha->format('Y-m-d') . '|' . $local->horario;
            if (isset($clavesEnCalendario[$clave])) {
                continue;
            }
            if ($local->cupo_reservado > 0) {
                continue; // Tiene reservas activas, se conserva aunque Unique ya no la ofrezca.
            }
            $local->delete();
            $fechasDeshabilitadas++;
        }

        return TourDisponibilidadSync::create([
            'tour_id' => $tour->id,
            'api_conexion_id' => $conexion->id,
            'origen' => $origen,
            'estado' => 'exitoso',
            'fechas_actualizadas' => $fechasActualizadas,
            'fechas_deshabilitadas' => $fechasDeshabilitadas,
        ]);
    }

    /**
     * Igual que sincronizarCalendario(), pero pensado para dispararse cada vez que un visitante
     * abre la ficha pública de un tour: se auto-limita a como mucho una corrida real cada
     * CACHE_TTL_SYNC_CALENDARIO_SEGUNDOS por tour (sin importar cuántas visitas haya en el medio),
     * y nunca lanza excepción — un error de sync nunca debe romper la carga de la página, solo
     * queda registrado en logs y en el historial.
     */
    public function sincronizarCalendarioSiNecesario(Tour $tour): void
    {
        if (!$tour->esApiExterna() || !$tour->sync_calendario_activo) {
            return;
        }

        $cacheKey = "calendario_sync_reciente:{$tour->id}";
        if (Cache::has($cacheKey)) {
            return;
        }
        Cache::put($cacheKey, true, self::CACHE_TTL_SYNC_CALENDARIO_SEGUNDOS);

        try {
            $this->sincronizarCalendario($tour, 'vista_publica');
        } catch (Throwable $e) {
            Log::warning("TourAvailabilitySyncService: error en sync automático de calendario del tour {$tour->id}: " . $e->getMessage());
        }
    }
}
