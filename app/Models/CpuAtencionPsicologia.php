<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CpuAtencionPsicologia extends Model
{
    use HasFactory;

    protected $table = 'cpu_atenciones_psicologia';
    protected $primaryKey = 'id';

    protected $fillable = [
        'id_cpu_atencion', // Asegúrate de que este campo está listo para recibir el ID
        'tipo_usuario',
        'tipo_atencion',
        'medio_atencion',
        'motivo_consulta',
        'evolucion',
        'diagnostico',
        'referido',
        'acciones_afirmativas',
        'consumo_sustancias',
        'frecuencia_consumo',
        'detalles_complementarios',
        'aspecto_actitud_presentacion',
        'aspecto_clinico',
        'sensopercepcion',
        'memoria',
        'ideacion',
        'pensamiento',
        'lenguaje',
        'juicio',
        'afectividad',
        'voluntad',
        'evolucion_caso',
        'abordaje_caso',
        'prescripcion',
        'descripcionfinal',
        'resu_reactivos',
    ];

    protected $casts = [
        'acciones_afirmativas' => 'array',
        'consumo_sustancias' => 'array',
        'frecuencia_consumo' => 'array',
        'detalles_complementarios' => 'array',
        'aspecto_actitud_presentacion' => 'array',
        'aspecto_clinico' => 'array',
        'sensopercepcion' => 'array',
        'memoria' => 'array',
        'ideacion' => 'array',
        'pensamiento' => 'array',
        'lenguaje' => 'array',
        'juicio' => 'array',
        'afectividad' => 'array',
        'voluntad' => 'array',
        'evolucion_caso' => 'array',
        'abordaje_caso' => 'array',
        'prescripcion' => 'array',
        'descripcionfinal' => 'array',
    ];
}