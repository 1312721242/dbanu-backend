<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CpuTipoUsuario extends Model
{
    use HasFactory;

    protected $table = 'cpu_tipo_usuario';

    protected $fillable = [
        'tipo_usuario',
    ];

    public function atenciones()
    {
        return $this->hasMany(CpuAtencion::class, 'usr_tipo', 'id');
    }
}
