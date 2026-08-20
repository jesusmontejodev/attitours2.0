<?php
/**
 * @file TourCambioPrecioApi.php
 * @description Modelo Eloquent para los cambios de precio detectados en syncs posteriores para tours ya publicados de una API externa. Ningún cambio se aplica solo: requiere aprobación explícita del Admin.
 * @date 2026-08-06
 * @author Claude
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TourCambioPrecioApi extends Model
{
    use HasFactory;

    protected $table = 'tour_cambios_precio_api';

    protected $fillable = [
        'tour_id',
        'api_conexion_id',
        'precio_referencia_anterior',
        'precio_referencia_nuevo',
        'precio_venta_actual',
        'detectado_at',
        'estado',
        'resuelto_por_user_id',
        'resuelto_at',
    ];

    protected $casts = [
        'precio_referencia_anterior' => 'float',
        'precio_referencia_nuevo' => 'float',
        'precio_venta_actual' => 'float',
        'detectado_at' => 'datetime',
        'resuelto_at' => 'datetime',
    ];

    /**
     * Relación: Pertenece a un Tour.
     */
    public function tour(): BelongsTo
    {
        return $this->belongsTo(Tour::class, 'tour_id');
    }

    /**
     * Relación: Pertenece a una Conexión API.
     */
    public function apiConexion(): BelongsTo
    {
        return $this->belongsTo(ApiConexion::class, 'api_conexion_id');
    }

    /**
     * Relación: Usuario Admin que aprobó o rechazó el cambio.
     */
    public function resueltoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resuelto_por_user_id');
    }
}
