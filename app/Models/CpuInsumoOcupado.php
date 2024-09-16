<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CpuInsumoOcupado extends Model
{
    use HasFactory;

    protected $table = 'cpu_insumos_ocupados';

    protected $fillable = [
        'id_insumo',
        'id_atencion_medicina_general',
        'id_funcionario',
        'id_paciente',
        'cantidad_ocupado',
        'detalle_ocupado',
        'fecha_uso',
    ];

    protected $casts = [
        'cantidad_ocupado' => 'decimal:2',
        'fecha_uso' => 'datetime',
    ];

    public function insumo()
    {
        return $this->belongsTo(CpuInsumo::class, 'id_insumo');
    }

    public function atencionMedicinaGeneral()
    {
        return $this->belongsTo(CpuAtencionMedicinaGeneral::class, 'id_atencion_medicina_general');
    }

    public function funcionario()
    {
        return $this->belongsTo(User::class, 'id_funcionario');
    }

    public function paciente()
    {
        return $this->belongsTo(CpuPersona::class, 'id_paciente');
    }
}
