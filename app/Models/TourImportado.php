<?php
/**
 * @file TourImportado.php
 * @description Modelo Eloquent para la bandeja de tours importados desde una API externa (no implementados), pendientes de revisión, descarte o publicación por el Admin.
 * @date 2026-08-06
 * @author Claude
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TourImportado extends Model
{
    use HasFactory;

    protected $table = 'tours_importados';

    protected $fillable = [
        'api_conexion_id',
        'locacion_externa_id',
        'external_id',
        'payload_raw',
        'titulo_preview',
        'descripcion_corta_preview',
        'descripcion_larga_preview',
        'imagen_preview',
        'precio_preview',
        'estado',
        'tour_id',
        'fecha_importado',
        'fecha_actualizado_catalogo',
    ];

    protected $casts = [
        'payload_raw' => 'array',
        'precio_preview' => 'float',
        'fecha_importado' => 'datetime',
        'fecha_actualizado_catalogo' => 'datetime',
    ];

    /**
     * Relación: Pertenece a una Conexión API.
     */
    public function apiConexion(): BelongsTo
    {
        return $this->belongsTo(ApiConexion::class, 'api_conexion_id');
    }

    /**
     * Relación: Tour real ya publicado a partir de este importado (si aplica).
     */
    public function tour(): BelongsTo
    {
        return $this->belongsTo(Tour::class, 'tour_id');
    }
}
