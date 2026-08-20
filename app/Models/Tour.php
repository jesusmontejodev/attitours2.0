<?php
/**
 * @file Tour.php
 * @description Modelo Eloquent para la tabla de tours, con soporte para atributos localizados en formato JSON, itinerarios dinámicos, inclusiones/exclusiones y tarifas de tours privados.
 * @date 2026-07-31
 * @author Antigravity
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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
        'tipo_modalidad',
        'tarifas_privadas',
        'anticipo_porcentaje',
        'duracion',
        'imagen_destacada',
        'galeria',
        'galeria_experiencias',
        'cupo_maximo',
        'tags',
        'horarios',
        'itinerario',
        'incluye',
        'no_incluye',
        'origen',
        'api_conexion_id',
        'api_tour_id_externo',
        'api_locacion_id',
        'precio_api_referencia_usd',
        'precio_api_actualizado_at',
        'api_metadata'
    ];

    protected $casts = [
        'titulo' => 'array',
        'descripcion_corta' => 'array',
        'descripcion_larga' => 'array',
        'galeria' => 'array',
        'galeria_experiencias' => 'array',
        'tags' => 'array',
        'precio_base_usd' => 'float',
        'tarifas_privadas' => 'array',
        'anticipo_porcentaje' => 'integer',
        'cupo_maximo' => 'integer',
        'horarios' => 'array',
        'itinerario' => 'array',
        'incluye' => 'array',
        'no_incluye' => 'array',
        'precio_api_referencia_usd' => 'float',
        'precio_api_actualizado_at' => 'datetime',
        'api_metadata' => 'array'
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
     * Relación: Conexión API de la que proviene este tour (si origen = api_externa).
     */
    public function apiConexion(): BelongsTo
    {
        return $this->belongsTo(ApiConexion::class, 'api_conexion_id');
    }

    /**
     * Relación: Registro de importación en la bandeja de tours importados que dio origen a este tour.
     */
    public function tourImportado(): HasOne
    {
        return $this->hasOne(TourImportado::class, 'tour_id');
    }

    /**
     * Relación: Cambios de precio detectados en syncs posteriores para este tour.
     */
    public function cambiosPrecioApi(): HasMany
    {
        return $this->hasMany(TourCambioPrecioApi::class, 'tour_id');
    }

    /**
     * Relación: Notificaciones de reserva enviadas a la API externa por reservas de este tour.
     */
    public function apiNotificaciones(): HasMany
    {
        return $this->hasMany(TourApiNotificacion::class, 'tour_id');
    }

    /**
     * Determina si el tour proviene de una API externa (vs. creado manualmente en la plataforma).
     */
    public function esApiExterna(): bool
    {
        return $this->origen === 'api_externa';
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

    /**
     * Calcula el precio total de un tour privado según el número de personas.
     */
    public function obtenerPrecioPrivado(int $pax): float
    {
        if (is_array($this->tarifas_privadas)) {
            foreach ($this->tarifas_privadas as $tarifa) {
                $min = (int) ($tarifa['min_pax'] ?? 0);
                $max = (int) ($tarifa['max_pax'] ?? 999);
                if ($pax >= $min && $pax <= $max) {
                    return (float) ($tarifa['precio_total_usd'] ?? $this->precio_base_usd);
                }
            }
        }

        // Fallback: si no hay tarifas privadas o no coincide con ninguna escala,
        // cobramos el precio base por pasajero.
        return (float) ($this->precio_base_usd * $pax);
    }

    /**
     * Determina si el tour admite la modalidad compartida.
     */
    public function esModalidadCompartida(): bool
    {
        return in_array($this->tipo_modalidad, ['compartido', 'ambos']);
    }

    /**
     * Determina si el tour admite la modalidad privada.
     */
    public function esModalidadPrivada(): bool
    {
        return in_array($this->tipo_modalidad, ['privado', 'ambos']);
    }
}
