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
        'id_sede',
        'id_facultad',
        'forma_pago',
        'id_user',
    ];

    public function funcionario()
    {
        return $this->belongsTo(CpuClientesTasty::class, 'id_funcionario_comunidad', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user', 'id');
    }

    public $timestamps = true;
}
