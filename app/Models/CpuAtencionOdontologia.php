<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CpuAtencionOdontologia extends Model
{
    use HasFactory;

    protected $table = 'cpu_atenciones_odontologia';

    protected $primaryKey = 'id_diente';

    public $incrementing = false;

    protected $fillable = [
        'id_cpu_atencion',
        'id_diente',
        'enfermedad_actual',
        'examenes_estomatognatico',
        'planes',
        'tratamiento',
    ];

    protected $casts = [
        'examenes_estomatognatico' => 'json',
        'planes' => 'json',
        'tratamiento' => 'json',
    ];

    public function atencion()
    {
        return $this->belongsTo(CpuAtenciones::class, 'id_cpu_atencion');
    }

    public function diente()
    {
        return $this->belongsTo(Diente::class, 'id_diente');
    }
}