<?php
/**
 * @file TourFecha.php
 * @description Modelo Eloquent para controlar las fechas de salida y la disponibilidad de cupos de los tours. Adaptado para bloquear la disponibilidad si hay reservas privadas.
 * @date 2026-07-31
 * @author Antigravity
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TourFecha extends Model
{
    use HasFactory;

    protected $table = 'tour_fechas';

    protected $fillable = [
        'tour_id',
        'fecha',
        'horario',
        'es_privado',
        'cupo_maximo',
        'cupo_reservado'
    ];

    protected $casts = [
        'fecha' => 'date:Y-m-d',
        'es_privado' => 'boolean',
        'cupo_maximo' => 'integer',
        'cupo_reservado' => 'integer'
    ];

    /**
     * Relación: Pertenece a un Tour.
     */
    public function tour(): BelongsTo
    {
        return $this->belongsTo(Tour::class, 'tour_id');
    }

    /**
     * Determina si esta fecha/horario de salida ya está bloqueado por una reserva privada activa.
     */
    public function estaBloqueadoPorReservaPrivada(): bool
    {
        return $this->es_privado && $this->cupo_reservado > 0;
    }

    /**
     * Accesor para calcular los cupos disponibles restantes. Si el tour proviene de una API
     * externa y su conexión tiene "Sincronizar Calendarios" activo, el cupo mostrado es el
     * mínimo entre el cupo local y el que reporta la API externa en tiempo real — así nunca se
     * sobrevende ni de un lado ni del otro (ver TourAvailabilitySyncService).
     */
    public function getCupoDisponibleAttribute(): int
    {
        if ($this->estaBloqueadoPorReservaPrivada()) {
            return 0;
        }

        $local = max(0, $this->cupo_maximo - $this->cupo_reservado);

        if ($this->tour && $this->tour->esApiExterna()) {
            $externo = app(\App\Services\TourAvailabilitySyncService::class)
                ->disponibilidadExterna($this->tour, $this->fecha->format('Y-m-d'), $this->horario);

            if ($externo !== null) {
                return min($local, max(0, $externo));
            }
        }

        return $local;
    }
}
