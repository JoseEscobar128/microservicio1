<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Direccion extends Model
{

    protected $table = 'direcciones';

    use SoftDeletes;

    protected $fillable = [
        'calle',
        'colonia',
        'ciudad',
        'estado',
        'codigo_postal'
    ];


    public function clientes(): HasMany
    {
        return $this->hasMany(Cliente::class);
    }
}
