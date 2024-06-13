<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CpuPersona extends Model
{
    use HasFactory;

    protected $fillable = [
        'cedula',
        'nombres',
        'nacionalidad',
        'provincia',
        'ciudad',
        'parroquia',
        'direccion',
        'sexo',
        'fechanaci',
        'celular',
        'tipoetnia',
        'discapacidad'
    ];

    protected $hidden = ['created_at', 'updated_at'];

    public function datosEmpleados()
    {
        return $this->hasOne(CpuDatosEmpleado::class, 'id_persona');
    }
}
