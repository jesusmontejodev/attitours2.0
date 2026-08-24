<?php
/**
 * @file TourDisponibilidadSync.php
 * @description Modelo Eloquent para el historial de sincronizaciones del calendario de un tour
 *              contra su API externa (ver TourAvailabilitySyncService::sincronizarCalendario).
 * @date 2026-08-22
 * @author Antigravity
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TourDisponibilidadSync extends Model
{
    protected $table = 'tour_disponibilidad_syncs';

    protected $fillable = [
        'tour_id',
        'api_conexion_id',
        'origen',
        'estado',
        'fechas_actualizadas',
        'fechas_deshabilitadas',
        'mensaje_error',
    ];

    protected $casts = [
        'fechas_actualizadas' => 'integer',
        'fechas_deshabilitadas' => 'integer',
    ];

    /**
     * Relación: Tour al que pertenece esta sincronización.
     */
    public function tour(): BelongsTo
    {
        return $this->belongsTo(Tour::class, 'tour_id');
    }

    /**
     * Relación: Conexión API contra la que se sincronizó.
     */
    public function apiConexion(): BelongsTo
    {
        return $this->belongsTo(ApiConexion::class, 'api_conexion_id');
    }
}
