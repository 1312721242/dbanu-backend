<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CpuPersona extends Model
{
    use HasFactory;

    protected $table = 'cpu_personas';

    protected $fillable = [
        'cedula', 'nombres', 'nacionalidad', 'provincia', 'ciudad', 'parroquia', 'direccion', 'sexo', 'fechanaci', 'celular', 'tipoetnia', 'discapacidad','codigo_persona','imagen','id_clasificacion_tipo_usuario'
    ];

    public function datosEmpleados()
    {
        return $this->hasOne(CpuDatosEmpleado::class, 'id_persona');
    }

    public function datosMedicos()
    {
        return $this->hasOne(CpuDatosMedicos::class, 'id_persona');
    }

    public function datosEstudiantes()
    {
        return $this->hasOne(CpuDatosEstudiantes::class, 'id_persona');
    }
}
