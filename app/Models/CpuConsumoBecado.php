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
        'id_sede',
        'id_facultad',
        'id_programa',
        'id_user',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user', 'id');
    }

    public $timestamps = true;
}
