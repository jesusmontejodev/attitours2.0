<?php
/**
 * @file ApiConexion.php
 * @description Modelo Eloquent para las conexiones a APIs externas de terceros (ej. Unique Reservaciones) usadas para importar catálogos de tours.
 * @date 2026-08-06
 * @author Claude
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApiConexion extends Model
{
    use HasFactory;

    protected $table = 'api_conexiones';

    protected $fillable = [
        'nombre',
        'proveedor_id',
        'base_url',
        'usuario',
        'password',
        'uniqid_empresa',
        'modo',
        'auth_token',
        'auth_token_expira_at',
        'headers_extra',
        'activa',
        'ultimo_sync_at',
        'ultimo_sync_status',
    ];

    protected $hidden = [
        'usuario',
        'password',
        'auth_token',
    ];

    protected $casts = [
        'usuario' => 'encrypted',
        'password' => 'encrypted',
        'auth_token' => 'encrypted',
        'auth_token_expira_at' => 'datetime',
        'headers_extra' => 'array',
        'activa' => 'boolean',
        'ultimo_sync_at' => 'datetime',
    ];

    /**
     * Determina si la conexión opera en modo SANDBOX.
     */
    public function esSandbox(): bool
    {
        return $this->modo === 'sandbox';
    }

    /**
     * Relación: Pertenece a un Proveedor "contenedor" de Attitour.
     */
    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id');
    }

    /**
     * Relación: Tours ya publicados que provienen de esta conexión.
     */
    public function tours(): HasMany
    {
        return $this->hasMany(Tour::class, 'api_conexion_id');
    }

    /**
     * Relación: Bandeja de tours importados (pendientes/descartados/publicados) de esta conexión.
     */
    public function toursImportados(): HasMany
    {
        return $this->hasMany(TourImportado::class, 'api_conexion_id');
    }

    /**
     * Relación: Cambios de precio detectados en syncs de esta conexión.
     */
    public function cambiosPrecio(): HasMany
    {
        return $this->hasMany(TourCambioPrecioApi::class, 'api_conexion_id');
    }

    /**
     * Relación: Notificaciones de reserva enviadas hacia esta conexión.
     */
    public function notificaciones(): HasMany
    {
        return $this->hasMany(TourApiNotificacion::class, 'api_conexion_id');
    }
}
