<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CpuDatosEmpleado extends Model
{
    use HasFactory;

    protected $fillable = [
        'id_persona',
        'emailinstitucional',
        'puesto',
        'regimen1',
        'modalidad',
        'unidad',
        'carrera',
        'idsubproceso',
        'escala1',
        'estado',
        'fechaingre'
    ];

    protected $hidden = ['created_at', 'updated_at'];

    public function persona()
    {
        return $this->belongsTo(CpuPersona::class, 'id_persona');
    }
}
