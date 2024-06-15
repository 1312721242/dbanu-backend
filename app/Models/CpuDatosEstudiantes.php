<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CpuDatosEstudiantes extends Model
{
    use HasFactory;

    protected $table = 'cpu_datos_estudiantes';

    protected $fillable = [
        'id_persona', 'campus', 'facultad', 'carrera', 'semestre_actual', 'estado_estudiante', 'estado_civil', 'email_institucional', 'email_personal', 'telefono', 'segmentacion_persona', 'periodo', 'estado_matricula'
    ];

    public function persona()
    {
        return $this->belongsTo(CpuPersona::class, 'id_persona');
    }
}

