<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CpuFuenteInformacion extends Model
{
    use HasFactory;
    protected $fillable = [
        'id_objetivo',
        'descripcion',
        'created_at',
        'update_at',
    ];

    // Relación con años
    public function objetivo()
    {
        return $this->belongsTo(CpuObjetivoNacional::class, 'descripcion', 'id');
    }
}
