<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CpuAtencion extends Model
{
    use HasFactory;

    protected $table = 'cpu_atenciones';

    protected $fillable = [
        'id_funcionario',
        'id_persona',
        'via_atencion',
        'motivo_atencion',
        'fecha_hora_atencion',
        'anio_atencion',
        'detalle_atencion',
        'id_caso',
    ];
}
