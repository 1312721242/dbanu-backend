<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CpuBecado extends Model
{
    use HasFactory;

    protected $table = 'cpu_becados';

    protected $fillable = [
        'periodo',
        'identificacion',
        'nombres',
        'apellidos',
        'sexo',
        'email',
        'telefono',
        'beca',
        'tipo_beca_otorgada',
        'monto_otorgado',
        'monto_consumido',
        'porcentaje_valor_arancel',
        'estado_postulante',
        'fecha_aprobacion_denegacion',
        'datos_bancarios_institucion_bancaria',
        'datos_bancarios_tipo_cuenta',
        'datos_bancarios_numero_cuenta',
        'promedio_dos_periodos_anteriores',
        'fecha_nacimiento',
        'estado_civil',
        'tipo_sangre',
        'ciudad_nacimiento',
        'direccion_residencia',
        'discapacidad',
        'tipo_discapacidad',
        'porcentaje_discapacidad',
        'numero_carnet_discapacidad',
        'matriz_extension',
        'facultad',
        'carrera',
        'carrera_codigo_senescyt',
        'curso_semestre',
        'numero_matricula',
    ];
}
