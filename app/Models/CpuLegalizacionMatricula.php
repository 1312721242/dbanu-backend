<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class CpuLegalizacionMatricula extends Model
{
    use HasApiTokens, HasFactory, Notifiable;
    protected $table = 'cpu_legalizacion_matricula';


    protected $fillable = [
        'id_periodo',
        'id_registro_nacional',
        'id_postulacion',
        'ciudad_campus',
        'id_sede',
        'id_facultad',
        'id_carrera',
        'email',
        'cedula',
        'apellidos',
        'nombres',
        'genero',
        'etnia',
        'discapacidad',
        'segmento_persona',
        'nota_postulacion',
        'fecha_nacimiento',
        'nacionalidad',
        'provincia_reside',
        'canton_reside',
        'parroquia_reside',
        'instancia_postulacion',
        'instancia_de_asignacion',
        'gratuidad',
        'observacion_gratuidad',
        'copia_identificacion',
        'estado_identificacion',
        'copia_titulo_acta_grado',
        'estado_titulo',
        'copia_aceptacion_cupo',
        'estado_cupo',
        'id_notificacion',
        'listo_para_revision',
        'legalizo_matricula',
        'create_at',
        'updated_at',
        'id_configuracion' ,
        'estado_identificacion' ,
        'estado_titulo' ,
        'estado_cupo' ,
        'tipo_matricula',
        
    ];

    public function facultad()
    {
        return $this->belongsTo(CpuFacultad::class, 'id_facultad');
    }

    public function carrera()
    {
        return $this->belongsTo(CpuCarrera::class, 'id_carrera');
    }

    public function sede()
    {
        return $this->belongsTo(CpuSede::class, 'id_sede');
    }
}
