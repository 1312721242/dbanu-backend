<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CpuEvidencia extends Model
{
    protected $table = 'cpu_evidencia';

    protected $fillable = [
        'id_fuente_informacion', 'descripcion', 'enlace_evidencia'
    ];

    public function fuenteInformacion()
    {
        return $this->belongsTo(CpuFuenteInformacion::class, 'id_fuente_informacion');
    }

    public function objetivoNacional()
    {
        return $this->belongsTo(CpuObjetivoNacional::class, 'id_fuente_informacion', 'id_objetivo');
    }
    


}
