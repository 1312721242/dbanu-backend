<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CpuDatosEmpleado extends Model
{
    use HasFactory;

    protected $table = 'cpu_datos_empleados';

    protected $fillable = [
        'id_persona',
        'emailinstitucional',
        'puesto',
        'regimen1',
        'modalidad',
        'unidad',
        'carrera',
        'nombreproceso',
        'correopersonal',
        'sector',
        'referencia',
        'estado',
        'fechaingre'
    ];

    protected $hidden = ['created_at', 'updated_at'];

    public function persona()
    {
        return $this->belongsTo(CpuPersona::class, 'id_persona');
    }
}
