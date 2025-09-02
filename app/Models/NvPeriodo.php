<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NvPeriodo extends Model
{
    use HasFactory;

    protected $table = 'nv.periodos';
    protected $primaryKey = 'id';

    protected $fillable = [
        'id_cpu_periodo',    // <- FK a public.cpu_periodo.id
        'nombre',
        'fecha_inicio',
        'fecha_fin',
        'activo',
        // ventanas de gestión
        'asistencia_inicio','asistencia_fin',
        'actas_inicio','actas_fin',
        'notas_inicio','notas_fin',
        // opcional: quién configuró
        'id_usuario',
    ];

    protected $casts = [
        'fecha_inicio'      => 'date',
        'fecha_fin'         => 'date',
        'asistencia_inicio' => 'datetime',
        'asistencia_fin'    => 'datetime',
        'actas_inicio'      => 'datetime',
        'actas_fin'         => 'datetime',
        'notas_inicio'      => 'datetime',
        'notas_fin'         => 'datetime',
        'activo'            => 'boolean',
    ];
    // Relaciones
    public function cpuPeriodo()
    {
        // Tabla existente en public
        return $this->belongsTo(\App\Models\CpuPeriodos::class, 'id_cpu_periodo', 'id');
    }
}
