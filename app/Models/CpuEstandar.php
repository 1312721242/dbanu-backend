<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CpuEstandar extends Model
{
    use HasFactory;

    protected $table = 'cpu_estandar';
    protected $primaryKey = 'id';

    // Define las relaciones
    public function objetivo()
    {
        return $this->belongsTo(CpuObjetivoNacional::class, 'id_objetivo', 'id');
    }

    // Define la relación con la tabla "estandar"
    public function estandar()
    {
        return $this->belongsTo(CpuEstandar::class, 'id_estandar', 'id');
    }
    public function elementosFundamentales()
    {
        return $this->hasMany(CpuElementoFundamental::class, 'id_estandar');
    }
}
