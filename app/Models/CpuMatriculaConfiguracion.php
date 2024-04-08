<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CpuMatriculaConfiguracion extends Model
{
    use HasFactory;
    protected $table = 'cpu_matricula_configuracion';

    protected $fillable = [
        'id_periodo', 'id_estado', 'fecha_inicio_matricula_ordinaria',
        'fecha_fin_matricula_ordinaria', 'fecha_inicio_matricula_extraordinaria',
        'fecha_fin_matricula_extraordinaria', 'fecha_inicio_habil_login',
        'fecha_fin_habil_login',
    ];

}
