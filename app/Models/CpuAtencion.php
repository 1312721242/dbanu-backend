<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CpuAtencion extends Model
{
    use HasFactory;

    protected $table = 'cpu_atenciones';

    protected $fillable = [
        'id_funcionario',
        'id_persona',
        'via_atencion',
        'motivo_atencion',
        'fecha_hora_atencion',
        'anio_atencion',
        'detalle_atencion',
        'id_caso',
        'id_tipo_usuario',
        'evolucion_enfermedad',
        'diagnostico',
        'prescripcion',
        'recomendacion',
        'tipo_atencion',
        'id_cie10',
        'id_estado',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'diagnostico' => 'array', // por si usas JSON
    ];

    // ⬇️ Relaciones requeridas por tu controlador
    public function diversidad()
    {
        return $this->hasOne(CpuAtencionesDiversidad::class, 'id_atencion');
    }

    public function beneficios()
    {
        return $this->hasOne(CpuAtencionesDivBeneficios::class, 'id_atencion');
    }
}
