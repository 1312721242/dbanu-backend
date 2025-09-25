<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CpuTurno extends Model
{
    use HasFactory;

    protected $table = 'cpu_turnos';
    protected $primaryKey = 'id_turnos';
    public $timestamps = false;

    protected $fillable = [
        'id_paciente',
        'id_medico',
        'fehca_turno',
        'hora',
        'estado',
        'area',
        'via_atencion',
        'usr_date_baja',
        'usr_date_creacion',
        'tipo_atencion',
    ];

    protected $casts = [
        'fehca_turno' => 'datetime',
        'usr_date_baja' => 'datetime',
        'usr_date_creacion' => 'datetime',
    ];
}
