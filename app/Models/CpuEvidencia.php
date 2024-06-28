<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CpuEvidencia extends Model
{
    protected $table = 'cpu_evidencia';

    protected $fillable = [
        'id_elemento_fundamental', 'descripcion', 'enlace_evidencia'
    ];

    public function fuenteInformacion()
    {
        return $this->belongsTo(CpuElementoFundamental::class, 'id_elemento_fundamental');
    }

    public function objetivoNacional()
    {
        return $this->belongsTo(cpu_indicador::class, 'id_elemento_fundamental', 'id_indicador');
    }



}
