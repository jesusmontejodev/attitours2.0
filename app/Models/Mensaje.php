<?php
/**
 * @file Mensaje.php
 * @description Modelo Eloquent para la mensajería interna cliente-admin (proxy de proveedor).
 * @date 2026-08-21
 * @author Antigravity
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Mensaje extends Model
{
    use HasFactory;

    protected $table = 'mensajes';

    protected $fillable = [
        'reserva_id',
        'reserva_tour_id',
        'remitente_tipo',
        'autor_user_id',
        'cuerpo',
        'contacto_destino_usado',
        'leido_por_cliente',
        'leido_por_admin',
    ];

    protected $casts = [
        'leido_por_cliente' => 'boolean',
        'leido_por_admin'   => 'boolean',
    ];

    public function reserva(): BelongsTo
    {
        return $this->belongsTo(Reserva::class, 'reserva_id');
    }

    public function reservaTour(): BelongsTo
    {
        return $this->belongsTo(ReservaTour::class, 'reserva_tour_id');
    }

    public function autor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'autor_user_id');
    }

    public function esDeCliente(): bool
    {
        return $this->remitente_tipo === 'cliente';
    }
}
