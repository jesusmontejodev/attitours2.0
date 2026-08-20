<?php
/**
 * @file TourApiNotificacion.php
 * @description Modelo Eloquent para la auditoría de las reservas creadas en la API externa (ej. POST /create de Unique) al pagarse una reserva de un tour de origen api_externa.
 * @date 2026-08-06
 * @author Claude
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TourApiNotificacion extends Model
{
    use HasFactory;

    protected $table = 'tour_api_notificaciones';

    protected $fillable = [
        'tour_id',
        'reserva_id',
        'reserva_tour_id',
        'api_conexion_id',
        'payload_enviado',
        'respuesta_http_status',
        'respuesta_body',
        'unique_reserva_id',
        'unique_confirma',
        'estado',
        'intentos',
        'proximo_intento_at',
    ];

    protected $casts = [
        'payload_enviado' => 'array',
        'respuesta_http_status' => 'integer',
        'intentos' => 'integer',
        'proximo_intento_at' => 'datetime',
    ];

    /**
     * Relación: Pertenece a un Tour.
     */
    public function tour(): BelongsTo
    {
        return $this->belongsTo(Tour::class, 'tour_id');
    }

    /**
     * Relación: Pertenece a una Reserva (cabecera).
     */
    public function reserva(): BelongsTo
    {
        return $this->belongsTo(Reserva::class, 'reserva_id');
    }

    /**
     * Relación: Pertenece a un detalle de Reserva (ReservaTour).
     */
    public function reservaTour(): BelongsTo
    {
        return $this->belongsTo(ReservaTour::class, 'reserva_tour_id');
    }

    /**
     * Relación: Pertenece a una Conexión API.
     */
    public function apiConexion(): BelongsTo
    {
        return $this->belongsTo(ApiConexion::class, 'api_conexion_id');
    }
}
