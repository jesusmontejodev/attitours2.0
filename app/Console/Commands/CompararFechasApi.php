<?php
/**
 * @file CompararFechasApi.php
 * @description Diagnóstico de solo lectura del calendario de tours sincronizados con Unique. Hace la
 *              misma consulta /sold_out que TourAvailabilitySyncService::sincronizarCalendario, guarda
 *              la respuesta cruda como evidencia y la compara fecha por fecha con los TourFecha locales,
 *              para saber si una diferencia de fechas viene de la API externa o de Attitour. No escribe
 *              en tour_fechas ni registra corridas en el historial de sincronizaciones.
 * @date 2026-10-12
 */

namespace App\Console\Commands;

use App\Models\Tour;
use App\Models\TourDisponibilidadSync;
use App\Models\TourFecha;
use App\Services\UniqueApiClient;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Throwable;

class CompararFechasApi extends Command
{
    protected $signature = 'tours:comparar-fechas
                            {tour? : ID del tour (sin él, lista los tours con sincronización activa)}
                            {--dias=60 : Días hacia adelante a comparar (el sync usa 60)}';

    protected $description = 'Compara las fechas que reporta Unique (/sold_out) con las fechas guardadas en Attitour, sin modificar nada';

    public function handle(): int
    {
        if (!$this->argument('tour')) {
            return $this->listarToursConSync();
        }

        $tour = Tour::with('apiConexion')->find($this->argument('tour'));

        if (!$tour) {
            $this->error('No existe el tour ' . $this->argument('tour'));

            return self::FAILURE;
        }

        if (!$tour->esApiExterna() || !$tour->apiConexion || !$tour->api_locacion_id || !$tour->api_tour_id_externo) {
            $this->error('El tour no está conectado a una API externa (origen, conexión, locación o servicio faltante).');

            return self::FAILURE;
        }

        $this->mostrarContexto($tour);

        // Mismas fechas que calcula sincronizarCalendario() para reproducir exactamente su consulta.
        $fechaInicio = now()->format('Y-m-d');
        $fechaFin = now()->addDays((int) $this->option('dias'))->format('Y-m-d');

        try {
            $respuesta = (new UniqueApiClient($tour->apiConexion))
                ->soldOut($fechaInicio, $tour->api_locacion_id, $tour->api_tour_id_externo, null, $fechaFin);
        } catch (Throwable $e) {
            $this->error('Falló la consulta a Unique: ' . $e->getMessage());

            return self::FAILURE;
        }

        $archivo = 'diagnostico/unique_sold_out_' . $tour->id . '_' . now()->format('Ymd_His') . '.json';
        Storage::disk('local')->put($archivo, json_encode([
            'consulta' => [
                'fecha' => $fechaInicio,
                'fecha_fin' => $fechaFin,
                'locacion' => $tour->api_locacion_id,
                'servicio' => $tour->api_tour_id_externo,
                'modo' => $tour->apiConexion->modo,
                'consultado_at_utc' => now()->toIso8601String(),
            ],
            'respuesta' => $respuesta,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $this->line("Respuesta cruda de Unique guardada en " . Storage::disk('local')->path($archivo));
        $this->newLine();

        [$unique, $formato, $sinFecha] = $this->normalizarRespuesta($respuesta);
        $this->line("Formato de la respuesta: {$formato}");

        if ($formato === 'lista') {
            $this->warn('  ⚠ Unique devolvió una lista plana, pero sincronizarCalendario() espera un objeto agrupado por fecha.');
            $this->warn('    Con este formato el sync no guarda ninguna fecha y borra las locales sin reservas: el problema es de Attitour.');
        }

        if ($sinFecha > 0) {
            $this->warn("  ⚠ {$sinFecha} elemento(s) sin fecha identificable: no se pueden ubicar en el calendario.");
        }

        $local = $this->fechasLocales($tour, $fechaInicio, $fechaFin);

        return $this->compararYMostrar($unique, $local);
    }

    private function listarToursConSync(): int
    {
        $tours = Tour::where('origen', 'api_externa')->get(['id', 'titulo', 'sync_calendario_activo', 'tipo_modalidad']);

        if ($tours->isEmpty()) {
            $this->warn('No hay tours importados de una API externa.');

            return self::SUCCESS;
        }

        $this->table(['ID', 'Tour', 'Sync calendario', 'Modalidad'], $tours->map(fn (Tour $tour) => [
            $tour->id,
            $tour->getLocalizedField('titulo', 'es'),
            $tour->sync_calendario_activo ? 'Sí' : 'No',
            $tour->tipo_modalidad ?? '-',
        ]));

        $this->line('Ejecuta: php artisan tours:comparar-fechas {ID}');

        return self::SUCCESS;
    }

    private function mostrarContexto(Tour $tour): void
    {
        $this->info('Tour: ' . $tour->getLocalizedField('titulo', 'es') . " ({$tour->id})");
        $this->line("Conexión: {$tour->apiConexion->nombre} | modo: {$tour->apiConexion->modo} | locación: {$tour->api_locacion_id} | servicio: {$tour->api_tour_id_externo}");
        $this->line('Sync de calendario activo: ' . ($tour->sync_calendario_activo ? 'Sí' : 'NO (las fechas no se actualizan desde Unique)'));
        $this->line('Modalidad: ' . ($tour->tipo_modalidad ?? '-') . ($tour->tipo_modalidad === 'mixto' ? ' (el lado privado no se sincroniza, se gestiona a mano)' : ''));
        $this->line('Hora del servidor: ' . now()->format('Y-m-d H:i') . ' ' . config('app.timezone')
            . ' | Cancún: ' . now()->setTimezone('America/Cancun')->format('Y-m-d H:i'));

        if (now()->format('Y-m-d') !== now()->setTimezone('America/Cancun')->format('Y-m-d')) {
            $this->warn('  ⚠ En UTC ya es otro día que en Cancún: el sync empieza en mañana y no actualiza las fechas de hoy.');
        }

        $syncs = TourDisponibilidadSync::where('tour_id', $tour->id)->latest('id')->take(5)->get();

        if ($syncs->isNotEmpty()) {
            $this->newLine();
            $this->line('Últimas sincronizaciones:');
            $this->table(['Fecha', 'Origen', 'Estado', 'Actualizadas', 'Deshabilitadas', 'Error'], $syncs->map(fn ($sync) => [
                $sync->created_at?->format('Y-m-d H:i'),
                $sync->origen,
                $sync->estado,
                $sync->fechas_actualizadas,
                $sync->fechas_deshabilitadas,
                mb_strimwidth((string) $sync->mensaje_error, 0, 60, '…'),
            ]));
        }
    }

    /**
     * Acepta los dos formatos posibles de /sold_out: el que asume el sync (objeto {fecha: [items]})
     * y el documentado por Unique (lista plana de items, con o sin "fecha" en cada uno).
     *
     * @return array{0: array<string, array>, 1: string, 2: int} [clave "fecha|horario" => items, formato, items sin fecha]
     */
    private function normalizarRespuesta(array $respuesta): array
    {
        $resultado = [];
        $sinFecha = 0;
        $formato = array_is_list($respuesta) ? 'lista' : 'agrupado por fecha';

        $agregar = function (?string $fecha, array $item) use (&$resultado, &$sinFecha) {
            $fecha = $this->normalizarFecha($fecha);
            $horario = $item['horario'] ?? null;

            if (!$fecha || !$horario) {
                $sinFecha++;

                return;
            }

            $resultado["{$fecha}|{$horario}"][] = $item;
        };

        if ($formato === 'lista') {
            foreach ($respuesta as $item) {
                if (is_array($item)) {
                    $agregar($item['fecha'] ?? null, $item);
                }
            }
        } else {
            foreach ($respuesta as $fecha => $items) {
                foreach (is_array($items) ? $items : [] as $item) {
                    if (is_array($item)) {
                        $agregar((string) $fecha, $item);
                    }
                }
            }
        }

        ksort($resultado);

        return [$resultado, $formato, $sinFecha];
    }

    private function normalizarFecha(?string $fecha): ?string
    {
        if (!$fecha) {
            return null;
        }

        try {
            return Carbon::parse($fecha)->format('Y-m-d');
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @return array<string, TourFecha> clave "fecha|horario"
     */
    private function fechasLocales(Tour $tour, string $fechaInicio, string $fechaFin): array
    {
        return TourFecha::where('tour_id', $tour->id)
            ->where('es_privado', $tour->tipo_modalidad === 'privado')
            ->whereBetween('fecha', [$fechaInicio, $fechaFin])
            ->orderBy('fecha')
            ->get()
            ->keyBy(fn (TourFecha $fecha) => $fecha->fecha->format('Y-m-d') . '|' . $fecha->horario)
            ->all();
    }

    private function compararYMostrar(array $unique, array $local): int
    {
        $claves = array_unique(array_merge(array_keys($unique), array_keys($local)));
        sort($claves);

        $filas = [];
        $conteo = ['OK' => 0];

        foreach ($claves as $clave) {
            [$fecha, $horario] = explode('|', $clave, 2);
            $items = $unique[$clave] ?? null;
            $tourFecha = $local[$clave] ?? null;

            $disponiblesUnique = $items ? min(array_map(fn ($item) => (int) ($item['disponibles'] ?? 0), $items)) : null;
            $agotadoUnique = $items && collect($items)->contains(fn ($item) => !empty($item['sold_out']));
            $libreLocal = $tourFecha ? max(0, $tourFecha->cupo_maximo - $tourFecha->cupo_reservado) : null;

            $estado = match (true) {
                $items && !$tourFecha => 'Falta en Attitour',
                !$items && $tourFecha && $tourFecha->cupo_reservado > 0 => 'Solo en Attitour (tiene reservas)',
                !$items && $tourFecha => 'Solo en Attitour',
                $agotadoUnique && $libreLocal > 0 => 'Agotado en Unique',
                $disponiblesUnique !== $libreLocal => 'Cupo distinto',
                default => 'OK',
            };

            $conteo[$estado] = ($conteo[$estado] ?? 0) + 1;

            if ($estado === 'OK') {
                continue;
            }

            $filas[] = [
                $fecha,
                $horario,
                $items ? $this->describirItems($items) : '-',
                $tourFecha ? "{$libreLocal} ({$tourFecha->cupo_reservado} reservados)" : '-',
                $estado,
            ];
        }

        $this->newLine();
        $this->line('Resumen: ' . collect($conteo)->map(fn ($n, $estado) => "{$estado}: {$n}")->implode(' | '));

        if (empty($filas)) {
            $this->info('✓ Las fechas y cupos de Attitour coinciden con lo que reporta Unique.');

            return self::SUCCESS;
        }

        $this->table(['Fecha', 'Horario', 'Unique', 'Attitour libre', 'Diferencia'], $filas);
        $this->line('Si Unique aquí no coincide con la web del cliente, la diferencia viene de su API: envíales el JSON guardado.');
        $this->line('Si Unique coincide con su web pero no con Attitour, la diferencia es de nuestro sync.');

        return self::SUCCESS;
    }

    private function describirItems(array $items): string
    {
        return collect($items)->map(function ($item) {
            $texto = ($item['disponibles'] ?? '?') . ' disp';
            $texto .= !empty($item['sold_out']) ? ' AGOTADO' : '';
            $texto .= isset($item['idioma']) ? " [{$item['idioma']}]" : '';

            return $texto;
        })->implode(', ');
    }
}
