<?php

namespace App\Models;
use App\Models\Cliente;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;



class OAuthAuthorizationCode extends Model
{
     use HasFactory;

    protected $table = 'oauth_authorization_codes';

    protected $fillable = ['cliente_id', 'usuario_id', 'code', 'expires_at'];

    protected $dates = ['expires_at'];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }
}
