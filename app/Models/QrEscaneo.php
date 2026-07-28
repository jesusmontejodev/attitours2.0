<?php
/**
 * @file QrEscaneo.php
 * @description Modelo Eloquent para el historial de escaneos de QR: registra cada intento
 *              (exitoso o no) realizado por un admin/proveedor, independientemente del resultado.
 * @date 2026-07-27
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QrEscaneo extends Model
{
    use HasFactory;

    protected $table = 'qr_escaneos';

    protected $fillable = [
        'reserva_id',
        'user_id',
        'resultado',
        'mensaje',
        'token_escaneado',
        'ip_address',
    ];

    /**
     * Relación: Reserva a la que apuntaba el QR escaneado (null si el token no existía).
     */
    public function reserva(): BelongsTo
    {
        return $this->belongsTo(Reserva::class, 'reserva_id');
    }

    /**
     * Relación: Usuario (admin o proveedor) que realizó el escaneo.
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
