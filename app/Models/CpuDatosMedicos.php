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
        'peso',
        'talla',
        'imc',
        'alergias',
        'embarazada',
        'ultima_fecha_mestruacion',
        'semanas_embarazo',
        'fecha_estimada_parto',
        'partos',
        'partos_data',
        'observacion_embarazo',
        'dependiente_medicamento',
        'medicamentos_dependiente',
        'tiene_seguro_medico',
        'detalles_alergias'
    ];

    protected $casts = [
        'enfermedades_catastroficas' => 'boolean',
        'detalle_enfermedades' => 'json',
        'embarazada' => 'boolean',
        'semanas_embarazo' => 'decimal:2',
        'dependiente_medicamento' => 'boolean',
        'medicamentos_dependiente' => 'json',
        'tiene_seguro_medico' => 'boolean',
        'detalles_alergias' => 'json',
        'partos' => 'boolean',
        'partos_data' => 'json'
    ];

   public function persona()
    {
        return $this->belongsTo(CpuPersona::class, 'id_persona', 'id');
    }

    public function tipoSangre()
    {
        return $this->belongsTo(CpuTipoSangre::class, 'tipo_sangre');
    }
}
