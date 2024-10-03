<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CpuAtencionMedicinaGeneral extends Model
{
    use HasFactory;

    protected $table = 'cpu_atenciones_medicina_general';

    protected $fillable = [
        'id_atencion',
        'organos_sistemas',
        'detalle_organos_sistemas',
        'examen_fisico',
        'detalle_examen_fisico',
        'insumos_medicos',
        'medicamentos',
    ];

    protected $casts = [
        'organos_sistemas' => 'boolean',
        'detalle_organos_sistemas' => 'json',
        'examen_fisico' => 'boolean',
        'detalle_examen_fisico' => 'json',
        'insumos_medicos' => 'json',
        'medicamentos' => 'json',
    ];

    public function atencion()
    {
        return $this->belongsTo(CpuAtencion::class, 'id_atencion');
    }
}
