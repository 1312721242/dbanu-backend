<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CpuTextosMensajes extends Model
{
    use HasFactory;
    public function funcionTexto()
{
    return $this->belongsTo(CpuFuncionesTextos::class, 'id_funciones_texto');
}

}
