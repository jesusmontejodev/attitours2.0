<?php
/**
 * @file Proveedor.php
 * @description Modelo Eloquent para la tabla de proveedores de tours.
 * @date 2026-06-09
 * @author Antigravity
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Proveedor extends Model
{
    use HasFactory;

    protected $table = 'proveedores';

    protected $fillable = [
        'nombre_empresa',
        'descripcion',
        'rfc',
        'correo',
        'representante_nombre',
        'representante_telefono',
        'contacto_visible_cliente',
        'comision_porcentaje',
        'foto_url'
    ];

    /**
     * Obtiene los tours asociados a este proveedor.
     */
    public function tours(): HasMany
    {
        return $this->hasMany(Tour::class, 'proveedor_id');
    }

    /**
     * Obtiene los usuarios asociados a este proveedor (usuarios del tipo 'PT').
     */
    public function usuarios(): HasMany
    {
        return $this->hasMany(User::class, 'proveedor_id');
    }

    /**
     * Obtiene las conexiones a APIs externas que agrupan sus tours importados bajo este proveedor.
     */
    public function apiConexiones(): HasMany
    {
        return $this->hasMany(ApiConexion::class, 'proveedor_id');
    }
}
