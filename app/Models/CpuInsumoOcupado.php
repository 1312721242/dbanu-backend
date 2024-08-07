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
        'id_funcionario',
        'cantidad_ocupado',
        'id_paciente',
        'detalle_ocupado',
        'fecha_uso',
    ];

    public function insumo()
    {
        return $this->belongsTo(CpuInsumo::class, 'id_insumo');
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
