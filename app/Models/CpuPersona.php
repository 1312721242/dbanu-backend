<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CpuPersona extends Model
{
    use HasFactory;

    protected $table = 'cpu_personas';

    protected $fillable = [
        'cedula', 'nombres', 'nacionalidad', 'provincia', 'ciudad', 'parroquia', 'direccion', 'sexo', 'fechanaci', 'celular', 'tipoetnia',
        'discapacidad','tipo_discapacidad','porcentaje_discapacidad','codigo_persona','imagen','id_clasificacion_tipo_usuario','ocupacion',
        'bono_desarrollo','estado_civil','id_tipo_usuario'
    ];

    public function datosEmpleados()
    {
        return $this->hasOne(CpuDatosEmpleado::class, 'id_persona');
    }

    public function datosExternos()
    {
        return $this->hasOne(CpuDatosUsuarioExterno::class, 'id_persona');
    }

     public function datosMedicos()
    {
        return $this->hasOne(CpuDatosMedicos::class, 'id_persona', 'id');
    }

    public function datosEstudiantes()
    {
        return $this->hasOne(CpuDatosEstudiantes::class, 'id_persona');
    }

    public function tipoUsuario()
    {
        return $this->belongsTo(CpuTipoUsuario::class, 'id_tipo_usuario');
    }

    public function tipoUsuarioClasificacion()
    {
        return $this->belongsTo(CpuTipoUsuario::class, 'id_clasificacion_tipo_usuario','clasificacion');
    }
    public function tipoDiscapacidad()
    {
        return $this->belongsTo(CpuTipoDiscapacidad::class, 'tipo_discapacidad', 'id');
    }

}
