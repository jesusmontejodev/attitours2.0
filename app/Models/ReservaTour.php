<?php
/**
 * @file ReservaTour.php
 * @description Modelo Eloquent para el detalle de cada tour reservado dentro de una compra. Incluye soporte para marcar la modalidad privada.
 * @date 2026-07-31
 * @author Antigravity
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ReservaTour extends Model
{
    use HasFactory;

    protected $table = 'reserva_tours';

    protected $fillable = [
        'reserva_id',
        'tour_id',
        'fecha_seleccionada',
        'horario',
        'cantidad_personas',
        'cantidad_adultos',
        'cantidad_menores',
        'cantidad_infantes',
        'es_privado',
        'precio_unitario_usd',
        'comision_usd',
        'folio_proveedor_externo',
        'idioma_seleccionado',
        'hotel_nombre',
        'hotel_lobby',
        'pickup_horario'
    ];

    protected $casts = [
        'fecha_seleccionada'  => 'date:Y-m-d',
        'cantidad_personas'   => 'integer',
        'cantidad_adultos'    => 'integer',
        'cantidad_menores'    => 'integer',
        'cantidad_infantes'   => 'integer',
        'es_privado'          => 'boolean',
        'precio_unitario_usd' => 'float',
        'comision_usd'        => 'float'
    ];

    /**
     * Relación: Pertenece a una cabecera de Reserva.
     */
    public function reserva(): BelongsTo
    {
        return $this->belongsTo(Reserva::class, 'reserva_id');
    }

    /**
     * Relación: Pertenece a un Tour.
     */
    public function tour(): BelongsTo
    {
        return $this->belongsTo(Tour::class, 'tour_id');
    }

    /**
     * Relación: Mensajes del chat interno específicos a este tour dentro de la reserva
     * (usado cuando una reserva incluye tours de proveedores distintos).
     */
    public function mensajes(): HasMany
    {
        return $this->hasMany(Mensaje::class, 'reserva_tour_id');
    }

    /**
     * Relación: Notificación enviada a la API externa para este detalle de reserva
     * (solo existe si el tour tiene origen = api_externa).
     */
    public function apiNotificacion(): HasOne
    {
        return $this->hasOne(TourApiNotificacion::class, 'reserva_tour_id');
    }
}
