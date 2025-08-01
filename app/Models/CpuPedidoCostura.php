<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CpuPedidoCostura extends Model
{
    protected $table = 'cpu_pedidos_costura';

    protected $fillable = [
        'id_persona',
        'tipo_usuario',
        'materiales',
        'prendas',
    ];

    protected $casts = [
        'prendas' => 'array',
    ];

    public $timestamps = true;
}
