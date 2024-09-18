<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CpuDatosMedicos extends Model
{
    use HasFactory;

    protected $table = 'cpu_datos_medicos';

    protected $fillable = [
        'id_persona',
        'enfermedades_catastroficas',
        'detalle_enfermedades',
        'tipo_sangre',
        'tiene_seguro_medico',
        'alergias',
        'detalles_alergias',
        'embarazada',
        'meses_embarazo',
        'observacion_embarazo',
        'dependiente_medicamento',
        'medicamentos_dependiente'
    ];

    protected $casts = [
        'enfermedades_catastroficas' => 'boolean',
        'detalle_enfermedades' => 'json',
        'tiene_seguro_medico' => 'boolean',
        'alergias' => 'boolean',
        'detalles_alergias' => 'json',
        'embarazada' => 'boolean',
        'meses_embarazo' => 'decimal:2',
        'dependiente_medicamento' => 'boolean',
        'medicamentos_dependiente' => 'json'
    ];

    public function persona()
    {
        return $this->belongsTo(CpuPersona::class, 'id_persona');
    }

    public function tipoSangre()
    {
        return $this->belongsTo(CpuTipoSangre::class, 'tipo_sangre');
    }
}
