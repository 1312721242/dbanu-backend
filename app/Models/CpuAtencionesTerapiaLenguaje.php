<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CpuAtencionesTerapiaLenguaje extends Model
{
    use HasFactory;

    protected $table = 'cpu_atenciones_terapia_lenguaje';
    protected $fillable = [
        'id_atencion', 'id_persona_padre', 'id_persona_madre',
        'numero_hermanos', 'antecedentes_embarazo', 'antecende_parto_nacido',
        'antecedente_morbico', 'desarollo_psicomotor_lenguaje',
        'mecanismo_oral_periferico', 'desarrollo_familiar', 'derivacion_externa'
    ];

    public function atencion()
    {
        return $this->belongsTo(CpuAtenciones::class, 'id_atencion');
    }
}
