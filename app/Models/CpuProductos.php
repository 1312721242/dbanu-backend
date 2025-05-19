<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CpuProductos extends Model
{
    use HasFactory;
    protected $fillable = [
        'pro_descripcion',
        'pro_codigo',
        'pro_estado',
        'pro_tipo',
        'pro_unidad_medida'
    ];
}
