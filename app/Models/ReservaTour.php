<?php
/**
 * @file ReservaTour.php
 * @description Modelo Eloquent para el detalle de cada tour reservado dentro de una compra.
 * @date 2026-06-08
 * @author Antigravity
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'precio_unitario_usd',
        'comision_usd'
    ];

    protected $casts = [
        'fecha_seleccionada' => 'date:Y-m-d',
        'cantidad_personas' => 'integer',
        'precio_unitario_usd' => 'float',
        'comision_usd' => 'float'
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
}
