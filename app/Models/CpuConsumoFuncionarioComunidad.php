<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CpuConsumoFuncionarioComunidad extends Model
{
    use HasFactory;

    protected $table = 'cpu_consumo_funcionario_comunidad';

    protected $fillable = [
        'id_funcionario_comunidad',
        'periodo',
        'identificacion',
        'tipo_alimento',
        'monto_facturado',
    ];

    public function funcionario()
    {
        return $this->belongsTo(CpuClientesTasty::class, 'id_funcionario_comunidad', 'id');
    }

    public $timestamps = true;
}
