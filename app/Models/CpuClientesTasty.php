<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CpuClientesTasty extends Model
{

    protected $table = 'cpu_funcionario_comunidad';

    protected $fillable = [
        'id',
        'identificacion',
        'nombres',
        'apellidos',
        'email',
        'telefono',
        'unidad_facultad_direccion',
        'cargo_puesto',
        'codigo_tarjeta',
        'fecha_inicio_valido',
        'fecha_fin_valido',
        'id_estado',
    ];

    public $timestamps = true;

    // Relación con la tabla de estados
    public function estado()
    {
        return $this->belongsTo(CpuEstado::class, 'id_estado');
    }
}
