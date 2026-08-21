<?php
/**
 * @file User.php
 * @description Modelo Eloquent para los usuarios del sistema. Modificado para incluir soporte a roles (Cliente, Proveedor, Admin) y datos adicionales.
 * @date 2026-06-08
 * @author Antigravity
 */

namespace App\Models;

use App\Notifications\VerifyEmailNotification;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'tipo', 'telefono', 'pais', 'proveedor_id', 'foto_perfil'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Envía la notificación de verificación de correo usando nuestra plantilla de marca
     * en vez del diseño markdown por defecto de Laravel.
     */
    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmailNotification());
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Relación: Pertenece a un Proveedor (si el tipo de usuario es 'PT').
     */
    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id');
    }

    /**
     * Relación: Reservas que ha realizado este cliente.
     */
    public function reservas(): HasMany
    {
        return $this->hasMany(Reserva::class, 'user_id');
    }

    /**
     * Helper para comprobar si el usuario es administrador.
     */
    public function isAdmin(): bool
    {
        return strtolower($this->tipo) === 'admin';
    }

    /**
     * Helper para comprobar si el usuario es proveedor.
     */
    public function isProveedor(): bool
    {
        return strtolower($this->tipo) === 'pt' || strtolower($this->tipo) === 'proveedor';
    }

    /**
     * Helper para comprobar si el usuario es cliente.
     */
    public function isCliente(): bool
    {
        return in_array(strtolower($this->tipo ?? ''), ['c', 'cliente', 'client']);
    }
}
