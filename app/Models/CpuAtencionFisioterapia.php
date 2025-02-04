<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CpuAtencionFisioterapia extends Model
{
    protected $table = 'cpu_atenciones_fisioterapia';

    protected $fillable = [
        'id_atencion',
        'partes',
        'subpartes',
        'eva',
        'test_goniometrico',
        'test_circunferencial',
        'test_longitudinal',
        'valoracion_fisioterapeutica',
        'diagnostico_fisioterapeutico',
        'aplicaciones_terapeuticas',
        'numero_comprobante',
        'valor_cancelado',
        'total_sesiones',
        'numero_sesion',

    ];

    protected $casts = [
        'eva' => 'integer',
        'test_goniometrico' => 'json',
        'test_circunferencial' => 'json',
        'test_longitudinal' => 'json',
        'aplicaciones_terapeuticas' => 'json',
    ];

    public function derivacion()
    {
        return $this->belongsTo(CpuDerivacion::class, 'id_derivacion');
    }
}
