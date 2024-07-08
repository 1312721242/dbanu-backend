<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CpuEstandar extends Model
{
    use HasFactory;

    protected $table = 'cpu_estandar';
    protected $primaryKey = 'id';

    protected $fillable = [
        'id_indicador',
        'descripcion',
    ];

    // Define la relación con la tabla "cpu_indicador"
    public function indicador()
    {
        return $this->belongsTo(CpuIndicador::class, 'id_indicador', 'id');
    }

    // Define la relación con la tabla "cpu_elemento_fundamental"
    public function elementosFundamentales()
    {
        return $this->hasMany(CpuElementoFundamental::class, 'id_estandar');
    }

    public $timestamps = true;
}
