<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CpuAtencionNutricion extends Model
{
    protected $table = 'cpu_atenciones_nutricion';

    protected $fillable = [
        'id_derivacion',
        // 'talla',
        // 'peso',
        // 'temperatura',
        // 'presion_sistolica',
        // 'presion_diastolica',
        'imc',
        'peso_ideal',
        'estado_paciente',
        'antecedente_medico',
        'motivo',
        'patologia',
        'recordatorio_24h',
        'analisis_clinicos',
        'alergias',
        'intolerancias',
        'nombre_plan_nutricional',
        'plan_nutricional',
        'permitidos',
        'no_permitidos',
    ];

    protected $casts = [
        'recordatorio_24h' => 'array',
        'alergias' => 'array',
        'intolerancias' => 'array',
        'plan_nutricional' => 'array',
        'permitidos' => 'array',
        'no_permitidos' => 'array',
    ];

    public function derivacion()
    {
        return $this->belongsTo(CpuDerivacion::class, 'id_derivacion');
    }
}
