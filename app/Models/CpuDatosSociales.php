<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CpuDatosSociales extends Model
{
    use HasFactory;

    protected $table = 'cpu_datos_sociales';

    protected $fillable = [
        'id_persona',
        'situacion_estudiante',
        'dormitorios_estudiante',
        'tipo_vivienda_estudiante',
        'estructura_estudiante',
        'servicios_basicos_estudiante',
        'situacion_familia',
        'dormitorios_familia',
        'tipo_vivienda_familia',
        'estructura_familia',
        'servicios_basicos_familia',
        'problema_salud',
        'diagnostico',
        'parentesco',
        'ingresos',
        'egresos',
        'diferencia',
        'markers',
        'image_path',
    ];

    protected $casts = [
        'estructura_estudiante' => 'array',
        'servicios_basicos_estudiante' => 'array',
        'estructura_familia' => 'array',
        'servicios_basicos_familia' => 'array',
        'ingresos' => 'array',
        'egresos' => 'array',
        'markers' => 'array',
    ];
}
