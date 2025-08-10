<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Usuario extends Authenticatable
{
    // 1. Usamos los traits. HasRoles debe estar aquí.
    use HasApiTokens, HasRoles, SoftDeletes;

    // 2. Definimos el guard_name para que Sanctum lo maneje
    protected $guard_name = 'api';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'usuarios';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'usuario',
        'email',
        'contrasena_hash',
        'empleado_id',
        'esta_activo',
        'otp_code',
        'otp_expires_at',
        'otp_verified_at',
        'email_verificado',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'contrasena_hash',
        'otp_code',
    ];

    /**
     * Define la relación: Un Usuario puede estar asociado a un Empleado.
     */
    public function empleado()
    {
        return $this->belongsTo(Empleado::class);
    }

    protected static function boot()
    {
        parent::boot();

        // Esta lógica se ejecuta cuando un usuario es soft-eliminado.
        static::deleting(function ($usuario) {
            // Desasocia todos los roles del usuario.
            $usuario->syncRoles([]);
        });
    }

    // Se eliminan los métodos hasPermissionTo y hasRole,
    // ya que el trait HasRoles los proporciona.
}