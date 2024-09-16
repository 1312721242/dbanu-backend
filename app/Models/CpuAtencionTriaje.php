<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CpuAtencionTriaje extends Model
{
    use HasFactory;

    protected $table = 'cpu_atenciones_triaje';

    protected $fillable = [
        'id_derivacion',
        'talla',
        'peso',
        'temperatura',
        'presion_sistolica',
        'presion_diastolica',
        'saturacion'
    ];
    public $timestamps = true;
}
