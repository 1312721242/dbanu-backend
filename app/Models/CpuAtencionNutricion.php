<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CpuAtencionNutricion extends Model
{
    protected $table = 'cpu_atenciones_nutricion';

    protected $fillable = [
        'ate_id',
        'imc',
        'peso_ideal',
        'estado_paciente',
        'antecedente_medico',
        'patologia',
        'recordatorio_24h',
        'analisis_clinicos',
        'alergias',
        'intolerancias',
        'permitidos',
        'no_permitidos',
        'nombre_plan_nutricional',
        'plan_nutricional',
    ];

    protected $casts = [
        'imc' => 'float',
        'peso_ideal' => 'float',
        'recordatorio_24h' => 'json',
        'alergias' => 'json',
        'intolerancias' => 'json',
        'permitidos' => 'json',
        'no_permitidos' => 'json',
        'plan_nutricional' => 'json',
    ];

    public function derivacion()
    {
        return $this->belongsTo(CpuDerivacion::class, 'id_derivacion');
    }
}
