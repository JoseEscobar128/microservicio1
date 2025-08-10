<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class Empleado extends Model
{
    use SoftDeletes, HasRoles, HasApiTokens;

    protected $guard_name = 'empleado';

    protected $fillable = [
        'nombre',
        'apellido_materno',
        'apellido_paterno',
        'telefono',
        'rfc',
        'curp',
        'nss',
        'fecha_contratacion',
        'estatus',
        'role_id',
        'esta_activo',
        'huella'
    ];

    /**
     * Relación uno a uno con Usuario
     */
    public function usuario(): HasOne
    {
        return $this->hasOne(Usuario::class);
    }
    public function rol()
    {
        return $this->belongsTo(\Spatie\Permission\Models\Role::class, 'role_id');
    }
}
