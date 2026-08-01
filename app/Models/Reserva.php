<?php
/**
 * @file Reserva.php
 * @description Modelo Eloquent para la cabecera de las reservas. Incluye soporte para saldos pendientes por pago fraccionado (tours privados) y QR único de asistencia.
 * @date 2026-07-31
 * @author Antigravity
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Reserva extends Model
{
    use HasFactory;

    protected $table = 'reservas';

    protected $fillable = [
        'user_id',
        'nombre_cliente',
        'correo_cliente',
        'telefono_cliente',
        'precio_total_usd',
        'monto_pagado_online_usd',
        'monto_pendiente_destino_usd',
        'comision_total_usd',
        'estado',
        'fecha_reserva',
        'ticket_codigo',
        'qr_token',
        'asistencia_confirmada',
        'asistencia_confirmada_at',
        'asistencia_confirmada_por',
        'stripe_session_id',
        'stripe_payment_intent_id',
    ];

    protected $casts = [
        'precio_total_usd'            => 'float',
        'monto_pagado_online_usd'     => 'float',
        'monto_pendiente_destino_usd' => 'float',
        'comision_total_usd'          => 'float',
        'fecha_reserva'               => 'datetime',
        'asistencia_confirmada'       => 'boolean',
        'asistencia_confirmada_at'    => 'datetime',
    ];

    // ─── Relaciones ──────────────────────────────────────────────────────────

    /**
     * Relación: Pertenece a un Usuario (opcional si compró sin cuenta).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relación: Tiene muchos tours detallados en la reserva.
     */
    public function detalles(): HasMany
    {
        return $this->hasMany(ReservaTour::class, 'reserva_id');
    }

    /**
     * Relación: Proveedor que confirmó la asistencia escaneando el QR.
     */
    public function proveedorConfirmador(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class, 'asistencia_confirmada_por');
    }

    // ─── Helpers QR ──────────────────────────────────────────────────────────

    /**
     * Genera un token QR único y seguro para esta reserva.
     * Formato: HMAC-SHA256 del id + ticket_codigo firmado con APP_KEY.
     */
    public static function generarQrToken(int $reservaId, string $ticketCodigo): string
    {
        $payload = $reservaId . '|' . $ticketCodigo . '|' . Str::random(16);
        return hash_hmac('sha256', $payload, config('app.key'));
    }

    /**
     * Devuelve el payload que se codifica en el QR: la URL de verificación de asistencia.
     * Al escanearla con la cámara del teléfono (fuera de la app) es un link tocable que lleva
     * directo a /dashboard/qr/verify/{token}; el propio escáner interno también la reconoce.
     */
    public function getQrPayload(): string
    {
        return route('dashboard.qr.verify', ['token' => $this->qr_token]);
    }

    /**
     * Devuelve la URL de la imagen QR generada por QuickChart (sin instalar paquetes).
     * Se puede usar directamente en <img src="...">.
     * Usa solo el token como payload para evitar el error 400 con IPs locales.
     */
    public function getQrImageUrl(int $size = 200): string
    {
        $payload = urlencode($this->getQrPayload());
        return "https://quickchart.io/qr?text={$payload}&size={$size}&format=png&margin=2&ecLevel=M";
    }

    /**
     * Verifica si un qr_token entrante es válido para esta reserva.
     */
    public function verificarToken(string $token): bool
    {
        return hash_equals($this->qr_token ?? '', $token);
    }
}
