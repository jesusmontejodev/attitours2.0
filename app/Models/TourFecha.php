<?php
/**
 * @file TourFecha.php
 * @description Modelo Eloquent para controlar las fechas de salida y la disponibilidad de cupos de los tours.
 * @date 2026-06-08
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
        'cupo_maximo',
        'cupo_reservado'
    ];

    protected $casts = [
        'fecha' => 'date:Y-m-d',
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
     * Accesor para calcular los cupos disponibles restantes.
     */
    public function getCupoDisponibleAttribute(): int
    {
        return max(0, $this->cupo_maximo - $this->cupo_reservado);
    }
}
