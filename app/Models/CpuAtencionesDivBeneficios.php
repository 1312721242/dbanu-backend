<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CpuAtencionesDivBeneficios extends Model
{
    use HasFactory;

    protected $table = 'cpu_atenciones_div_beneficios';

    protected $fillable = [
        'id_atencion',
        'recibe_incentivo',
        'recibe_credito',
        'recibe_beca',
        'anio_beca',
        'detalle',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'recibe_incentivo' => 'boolean',
        'recibe_credito'   => 'boolean',
        'recibe_beca'      => 'boolean',
        'anio_beca'        => 'integer',
        'detalle'          => 'array', // jsonb
    ];

    public function atencion()
    {
        return $this->belongsTo(CpuAtencion::class, 'id_atencion');
    }
}
