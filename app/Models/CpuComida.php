<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CpuComida extends Model
{
    use HasFactory;

    protected $table = 'cpu_comida';

    protected $fillable = [
        'id_tipo_comida',
        'descripcion',
        'precio',
    ];

    protected $appends = [
        'descripcion_tipo_comida',
    ];

    // Relación con el modelo CpuTipoComida (relación muchos a uno)
    public function tipoComida()
    {
        return $this->belongsTo(CpuTipoComida::class, 'id_tipo_comida');
    }

    // Atributo adicional para la descripción del tipo de comida
    public function getDescripcionTipoComidaAttribute()
    {
        return $this->tipoComida->descripcion;
    }
}
