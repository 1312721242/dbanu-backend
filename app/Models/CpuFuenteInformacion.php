<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CpuFuenteInformacion extends Model
{
    use HasFactory;
    protected $table = 'cpu_fuente_informacion';

    protected $fillable = [
        'id_objetivo',
        'descripcion',
        'created_at',
        'update_at',
        'id_sede',
    ];

    // Relación con años
    public function objetivo()
    {
        return $this->belongsTo(CpuObjetivoNacional::class, 'descripcion', 'id');
    }
    public function evidencias()
    {
        return $this->hasMany(CpuEvidencia::class, 'id_fuente_informacion');
    }
    public function sede()
    {
        return $this->belongsTo(CpuSede::class, 'id_sede');
    }
     
}
