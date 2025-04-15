<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CpuFuncionarioComunidad extends Model
{
    protected $table = 'cpu_funcionario_comunidad';

    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'identificacion',
        'nombres',
        'apellidos',
        'email',
        'telefono',
        'unidad_facultad_direccion',
        'cargo_puesto',
        'regimen',
        'estado_tarjeta',
        'codigo_tarjeta',
        'fecha_inicio_valido',
        'fecha_fin_valido',
        'id_estado',
    ];

    protected $casts = [
        'fecha_inicio_valido' => 'date',
        'fecha_fin_valido' => 'date',
    ];

    public function estado()
    {
        return $this->belongsTo(CpuEstado::class, 'id_estado');
    }
}
