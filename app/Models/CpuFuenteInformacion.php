<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CpuFuenteInformacion extends Model
{
    use HasFactory;

    protected $table = 'cpu_fuente_informacion';
    protected $fillable = ['id_indicador', 'descripcion'];

    public function indicador()
    {
        return $this->belongsTo(CpuIndicador::class, 'id_indicador', 'id');
    }
}
