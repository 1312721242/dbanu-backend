<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CpuAtencionesDiversidad extends Model
{
    protected $table = 'cpu_atenciones_diversidad';

    protected $fillable = [
        'id_atencion',
        'carrera','status_academico','nivel_academico',
        'fecha_inicio_primer_nivel','fecha_ingreso_convalidacion',
        'fecha_inicio_periodo','fecha_fin_periodo','segmentacion',
        'atencion_en_salud','lactancia','numero_hijos','hijos_con_discapacidad',
        'enfermedad_catastrofica','adaptacion_curricular','acompanamiento_academico',
        'tutor_asignado','observacion'
    ];

    protected $casts = [
        'fecha_inicio_primer_nivel' => 'date',
        'fecha_ingreso_convalidacion' => 'date',
        'fecha_inicio_periodo' => 'date',
        'fecha_fin_periodo' => 'date',
        'atencion_en_salud' => 'boolean',
        'lactancia' => 'boolean',
        'enfermedad_catastrofica' => 'boolean',
        'acompanamiento_academico' => 'boolean',
    ];

    public function atencion(){ return $this->belongsTo(CpuAtencion::class, 'id_atencion'); }
}
