<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CpuConsumoBecado extends Model
{
    use HasFactory;

    protected $table = 'cpu_consumo_becado';

    protected $fillable = [
        'id_becado',
        'periodo',
        'identificacion',
        'tipo_alimento',
        'monto_facturado',
    ];

    public $timestamps = true;
}
