<?php
/**
 * @file Tour.php
 * @description Modelo Eloquent para la tabla de tours, con soporte para atributos localizados en formato JSON, itinerarios dinámicos e inclusiones/exclusiones.
 * @date 2026-06-09
 * @author Antigravity
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tour extends Model
{
    use HasFactory;

    protected $table = 'tours';

    // El ID no es un entero incremental, es un código string
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'proveedor_id',
        'titulo',
        'descripcion_corta',
        'descripcion_larga',
        'ubicacion',
        'punto_encuentro',
        'pais',
        'precio_base_usd',
        'duracion',
        'imagen_destacada',
        'galeria',
        'cupo_maximo',
        'tags',
        'horarios',
        'itinerario',
        'incluye',
        'no_incluye'
    ];

    protected $casts = [
        'titulo' => 'array',
        'descripcion_corta' => 'array',
        'descripcion_larga' => 'array',
        'galeria' => 'array',
        'tags' => 'array',
        'precio_base_usd' => 'float',
        'cupo_maximo' => 'integer',
        'horarios' => 'array',
        'itinerario' => 'array',
        'incluye' => 'array',
        'no_incluye' => 'array'
    ];

    /**
     * Relación: Pertenece a un Proveedor.
     */
    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id');
    }

    /**
     * Relación: Tiene muchas fechas de salida / disponibilidad.
     */
    public function fechas(): HasMany
    {
        return $this->hasMany(TourFecha::class, 'tour_id');
    }

    /**
     * Relación: Detalle de reservas asociadas a este tour.
     */
    public function reservaTours(): HasMany
    {
        return $this->hasMany(ReservaTour::class, 'tour_id');
    }

    /**
     * Accesor para obtener el título en el idioma actual de la aplicación.
     */
    public function getNombreAttribute(): string
    {
        return $this->getLocalizedField('titulo');
    }

    /**
     * Accesor para obtener la descripción corta en el idioma actual de la aplicación.
     */
    public function getResumenAttribute(): string
    {
        return $this->getLocalizedField('descripcion_corta');
    }

    /**
     * Accesor para obtener la descripción larga en el idioma actual de la aplicación.
     */
    public function getDetalleAttribute(): string
    {
        return $this->getLocalizedField('descripcion_larga');
    }

    /**
     * Método helper para extraer el campo localizado según el idioma activo.
     */
    public function getLocalizedField(string $fieldName, ?string $locale = null): string
    {
        $locale = $locale ?: app()->getLocale();
        $arrayData = $this->getAttribute($fieldName);

        if (!is_array($arrayData)) {
            return '';
        }

        // Retornar en el locale actual, o fallback al español, o el primer idioma disponible
        return $arrayData[$locale] ?? $arrayData['es'] ?? reset($arrayData) ?? '';
    }
}
