<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CpuDatosMedicos extends Model
{
    use HasFactory;

    protected $table = 'cpu_datos_medicos';

    protected $fillable = [
        'id_persona', 'enfermedades_catastroficas', 'detalle_enfermedades', 'tipo_sangre', 'peso', 'talla', 'imc', 'alergias', 'embarazada', 'meses_embarazo', 'observacion_embarazo', 'dependiente_medicamento', 'medicamentos_dependiente'
    ];

    public function persona()
    {
        return $this->belongsTo(CpuPersona::class, 'id_persona');
    }
}
