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
        'galeria_experiencias',
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
        'galeria_experiencias' => 'array',
        'tags' => 'array',
        'precio_base_usd' => 'float',
        'cupo_maximo' => 'integer',
        'horarios' => 'array',
        'itinerario' => 'array',
        'incluye' => 'array',
        'no_incluye' => 'array'
    ];

    /**
     * Devuelve la lista de fotos de la Galería de Experiencias de viajeros.
     * Si no hay fotos personalizadas registradas, devuelve una selección de fotos reales.
     */
    public function getExperienciasFotosAttribute(): array
    {
        if (is_array($this->galeria_experiencias) && count($this->galeria_experiencias) > 0) {
            return $this->galeria_experiencias;
        }

        // Fotos reales de experiencias de viajeros por defecto
        return [
            'https://images.unsplash.com/photo-1544551763-46a013bb70d5?auto=format&fit=crop&w=800&q=80',
            'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=800&q=80',
            'https://images.unsplash.com/photo-1519046904884-53103b34b206?auto=format&fit=crop&w=800&q=80',
            'https://images.unsplash.com/photo-1534447677768-be436bb09401?auto=format&fit=crop&w=800&q=80',
            'https://images.unsplash.com/photo-1510414842594-a61c69b5ae57?auto=format&fit=crop&w=800&q=80',
            'https://images.unsplash.com/photo-1506929562872-bb421503ef21?auto=format&fit=crop&w=800&q=80'
        ];
    }

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
