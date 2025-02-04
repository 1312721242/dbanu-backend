<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CpuAtencionTriaje extends Model
{
    use HasFactory;

    protected $table = 'cpu_atenciones_triaje';

    protected $fillable = [
        'id_atencion',
        'talla',
        'peso',
        'temperatura',
        'saturacion',
        'presion_sistolica',
        'presion_diastolica',
        'saturacion',
        'imc',
        'peso_ideal',
        'estado_paciente'
    ];
    public $timestamps = true;
}
