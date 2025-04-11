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
        'id_sede',
        'id_facultad',
    ];

    protected $appends = [
        'descripcion_tipo_comida',
    ];

    // Relación con el modelo CpuTipoComida (relación muchos a uno)
    public function tipoComida()
    {
        return $this->belongsTo(CpuTipoComida::class, 'id_tipo_comida');
    }

    // Relación con sede (muchos a uno)
    public function sede()
    {
        return $this->belongsTo(CpuSede::class, 'id_sede');
    }

    // Relación con sede (muchos a uno)
    public function facultad()
    {
        return $this->belongsTo(CpuFacultad::class, 'id_facultad');
    }

    // Atributo adicional para la descripción del tipo de comida
    public function getDescripcionTipoComidaAttribute()
    {
        return $this->tipoComida->descripcion;
    }
}
