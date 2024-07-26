<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CpuAspirantesEvaluaciones extends Model
{
    use HasFactory;

    protected $table = 'cpu_aspirantes_evaluaciones';

    protected $fillable = [
        'idaspirante', 'idaspiranteinscripcion', 'periodo', 'identifica', 'apellidos', 'nombres',
        'correo_uleam', 'canton_uleam', 'idperiodooferta', 'idcarrera', 'carrera', 'tipo_evaluacion',
        'idsedeuleam', 'sede', 'idcampoamplio', 'descripcion_campo_amplio', 'canton_senescyt',
        'celular_uleam', 'celular_senescyt', 'correo_senescyt', 'nacionalidad', 'estado_civil',
        'estado_registro_nacional', 'sexo', 'genero', 'pais_residencia', 'provincia_residencia',
        'canton_residencia', 'parroquia_residencia', 'autoidentificacion', 'pueblo_indigena',
        'ppl', 'nombre_centro_ppl_cai', 'ins_poblacion', 'tipo_evalu', 'grupo_ca', 'zona',
        'bloque', 'sala', 'fecha', 'horario', 'cod_lugar'
    ];
}
