<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CpuAtencionesTrabajoSocial extends Model
{
    use HasFactory;

    protected $table = 'cpu_atenciones_trabajo_social';

    protected $fillable = [
        'id_atenciones',
        'tipo_informe',
        'requiriente',
        'numero_tramite',
        'detalle_general',
        'observaciones',
        'url_informe',
        'periodo',
        'created_at',
        'updated_at'
    ];
}
