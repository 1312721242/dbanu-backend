<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CpuDatosUsuarioExterno extends Model
{
    use HasFactory;
    protected $table = 'cpu_datos_usuarios_externos';
    protected $fillable = ['id_persona', 'email', 'referencia', 'numero_matricula', 'tipo_beca'];

    public function persona()
    {
        return $this->belongsTo(CpuPersona::class, 'id_persona');
    }
}
