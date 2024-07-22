<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CpuValorConsumoDiarioBeca extends Model
{
    use HasFactory;

    protected $table = 'cpu_valor_consumo_diario_becas';
    public $timestamps = false; // Assuming no timestamps columns

    protected $fillable = [
        'valor',
    ];
}
