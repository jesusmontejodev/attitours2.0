<?php
/**
 * @file TourCatalogSyncService.php
 * @description Orquesta la sincronización de catálogo con una API externa (Unique): recorre locaciones y servicios, guarda tours nuevos en la bandeja de importados (tours_importados) y, para tours ya publicados, detecta cambios de precio sin aplicarlos solos (quedan pendientes de aprobación en tour_cambios_precio_api).
 * @date 2026-08-06
 * @author Claude
 */

namespace App\Services;

use App\Models\ApiConexion;
use App\Models\Tour;
use App\Models\TourCambioPrecioApi;
use App\Models\TourImportado;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class TourCatalogSyncService
{
    /**
     * Umbral mínimo de diferencia de precio para considerarlo un cambio real
     * (evita ruido por redondeos de centavos).
     */
    private const UMBRAL_CAMBIO_PRECIO = 0.01;

    /**
     * Ejecuta el sync completo de una conexión. Devuelve un resumen para mostrar al Admin.
     */
    public function sync(ApiConexion $conexion): array
    {
        $client = new UniqueApiClient($conexion);

        $resumen = [
            'nuevos' => 0,
            'actualizados' => 0,
            'cambios_precio_detectados' => 0,
            'errores' => [],
        ];

        try {
            $locaciones = $client->locations();
        } catch (Throwable $e) {
            $this->registrarResultado($conexion, false, $e->getMessage());
            throw $e;
        }

        $fechaHoy = now()->format('Y-m-d');

        // Se han observado respuestas de Unique con locaciones o servicios repetidos dentro
        // de una misma llamada (inconsistencia del lado del proveedor) — se deduplica por Id
        // para no procesar el mismo servicio dos veces en un solo sync.
        $locacionesVistas = [];

        foreach ($locaciones as $locacion) {
            $locacionId = $locacion['Id'] ?? null;
            if (!$locacionId || isset($locacionesVistas[$locacionId])) {
                continue;
            }
            $locacionesVistas[$locacionId] = true;

            try {
                $servicios = $client->services($locacionId);
            } catch (Throwable $e) {
                $resumen['errores'][] = "Locación {$locacionId}: {$e->getMessage()}";
                continue;
            }

            $serviciosVistos = [];

            foreach ($servicios as $servicio) {
                $servicioId = $servicio['Id'] ?? null;
                if (!$servicioId || isset($serviciosVistos[$servicioId])) {
                    continue;
                }
                $serviciosVistos[$servicioId] = true;

                $this->procesarServicio($client, $conexion, $locacionId, $servicio, $fechaHoy, $resumen);
            }
        }

        $this->registrarResultado($conexion, true, null);

        return $resumen;
    }

    /**
     * Procesa un servicio/actividad individual: si ya es un Tour publicado, solo compara precio;
     * si no, hace upsert en la bandeja de tours_importados.
     */
    private function procesarServicio(
        UniqueApiClient $client,
        ApiConexion $conexion,
        string $locacionId,
        array $servicio,
        string $fechaHoy,
        array &$resumen
    ): void {
        $servicioId = $servicio['Id'] ?? null;
        if (!$servicioId) {
            return;
        }

        $externalId = $locacionId . ':' . $servicioId;

        $precioApi = null;
        try {
            $preciosServicio = $client->prices($fechaHoy, $locacionId, $servicioId);
            $primero = $preciosServicio[0] ?? null;
            if ($primero && isset($primero['precio'])) {
                $precioApi = (float) $primero['precio'];
            }
        } catch (Throwable $e) {
            Log::warning("TourCatalogSyncService: no se pudo obtener precio de Unique para {$externalId}: " . $e->getMessage());
        }

        $tourPublicado = Tour::where('api_conexion_id', $conexion->id)
            ->where('api_locacion_id', $locacionId)
            ->where('api_tour_id_externo', $servicioId)
            ->first();

        if ($tourPublicado) {
            $this->compararYRegistrarCambioPrecio($tourPublicado, $conexion, $precioApi, $resumen);

            return;
        }

        $this->upsertImportado($conexion, $locacionId, $externalId, $servicio, $precioApi, $resumen);
    }

    /**
     * Compara el precio recién consultado contra el de referencia guardado. Si difiere,
     * genera (o actualiza) un cambio pendiente de aprobación — nunca sobrescribe solo.
     */
    private function compararYRegistrarCambioPrecio(Tour $tour, ApiConexion $conexion, ?float $precioApi, array &$resumen): void
    {
        if ($precioApi === null) {
            return;
        }

        // Primera vez que se logra leer un precio de referencia para este tour: se guarda directo,
        // no hay "cambio" que aprobar porque no existía un valor previo con el que compararlo.
        if ($tour->precio_api_referencia_usd === null) {
            $tour->update([
                'precio_api_referencia_usd' => $precioApi,
                'precio_api_actualizado_at' => now(),
            ]);

            return;
        }

        $diferencia = abs($precioApi - (float) $tour->precio_api_referencia_usd);
        if ($diferencia < self::UMBRAL_CAMBIO_PRECIO) {
            return;
        }

        $datos = [
            'precio_referencia_anterior' => $tour->precio_api_referencia_usd,
            'precio_referencia_nuevo' => $precioApi,
            'precio_venta_actual' => $tour->precio_base_usd,
            'detectado_at' => now(),
        ];

        $pendiente = TourCambioPrecioApi::where('tour_id', $tour->id)->where('estado', 'pendiente')->first();

        if ($pendiente) {
            $pendiente->update($datos);
        } else {
            TourCambioPrecioApi::create(array_merge($datos, [
                'tour_id' => $tour->id,
                'api_conexion_id' => $conexion->id,
                'estado' => 'pendiente',
            ]));
            $resumen['cambios_precio_detectados']++;
        }
    }

    /**
     * Hace upsert de un servicio no publicado todavía en la bandeja tours_importados.
     * No toca el 'estado' de un registro existente (para no revivir uno descartado o publicado).
     */
    private function upsertImportado(
        ApiConexion $conexion,
        string $locacionId,
        string $externalId,
        array $servicio,
        ?float $precioApi,
        array &$resumen
    ): void {
        $camposComunes = [
            'locacion_externa_id' => $locacionId,
            'payload_raw' => $servicio,
            'titulo_preview' => $servicio['Titulo'] ?? $servicio['servicio'] ?? null,
            'descripcion_corta_preview' => $servicio['Descripcion_corta'] ?? null,
            'descripcion_larga_preview' => $servicio['Descripcion_larga'] ?? null,
            'imagen_preview' => $servicio['imagen'] ?? null,
            'precio_preview' => $precioApi,
            'fecha_actualizado_catalogo' => now(),
        ];

        $importado = TourImportado::where('api_conexion_id', $conexion->id)
            ->where('external_id', $externalId)
            ->first();

        if ($importado) {
            $importado->update($camposComunes);
            $resumen['actualizados']++;

            return;
        }

        TourImportado::create(array_merge($camposComunes, [
            'api_conexion_id' => $conexion->id,
            'external_id' => $externalId,
            'estado' => 'pendiente',
            'fecha_importado' => now(),
        ]));
        $resumen['nuevos']++;
    }

    private function registrarResultado(ApiConexion $conexion, bool $ok, ?string $error): void
    {
        $conexion->update([
            'ultimo_sync_at' => now(),
            'ultimo_sync_status' => $ok ? 'ok' : ('error: ' . Str::limit($error ?? 'desconocido', 480)),
        ]);
    }
}
