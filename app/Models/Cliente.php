<?php

namespace App\Models;

use Illuminate\Container\Attributes\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Foundation\Auth\User as Authenticatable;


class Cliente extends Authenticatable
{
    use SoftDeletes, HasApiTokens, HasRoles;

    protected $guard_name = 'cliente';

    protected $fillable = [
        'email', 'nombre', 'apellido', 'direccion_id',
        'contrasena_hash', 'google_id', 'telefono',
        'otp_code', 'otp_expires_at', 'email_verificado'
    ];

    protected $hidden = [
        'contrasena_hash', 'otp_code'
    ];

    protected $casts = [
        'otp_expires_at' => 'datetime',
        'otp_verified_at' => 'datetime',
        'email_verificado' => 'boolean'
    ];

    public function direccion(): BelongsTo
    {
        return $this->belongsTo(Direccion::class);
    }
}
