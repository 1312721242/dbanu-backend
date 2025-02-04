<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CpuDerivacion extends Model
{
    use HasFactory;

    protected $table = 'cpu_derivaciones';
    protected $primaryKey = 'id';

    protected $fillable = [
        'ate_id',
        'id_doctor_al_que_derivan',
        'id_paciente',
        'fecha_derivacion',
        'motivo_derivacion',
        'diagnostico',
        'detalle_derivacion',
        'id_area',
        'id_estado_derivacion',
        'fecha_para_atencion',
        'hora_para_atencion',
        'id_funcionario_que_derivo',
        'id_turno_asignado',
    ];

    // Relación con la tabla cpu_atenciones
    public function atencion()
    {
        return $this->belongsTo(CpuAtencion::class, 'ate_id');
    }

    // Relación con la tabla cpu_estados
    public function estado()
    {
        return $this->belongsTo(CpuEstado::class, 'id_estado_derivacion');
    }

    // Relación con la tabla users (funcionario que derivó)
    public function funcionarioQueDerivo()
    {
        return $this->belongsTo(User::class, 'id_funcionario_que_derivo');
    }

    // Relación con la tabla users (doctor al que derivan)
    public function doctorAlQueDerivan()
    {
        return $this->belongsTo(User::class, 'id_doctor_al_que_derivan');
    }

    // Relación con la tabla cpu_personas
    public function paciente()
    {
        return $this->belongsTo(CpuPersona::class, 'id_paciente');
    }

    // Relación con la tabla cpu_turnos
    public function turnoAsignado()
    {
        return $this->belongsTo(CpuTurno::class, 'id_turno_asignado');
    }
}
