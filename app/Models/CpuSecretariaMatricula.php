<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CpuSecretariaMatricula extends Model
{
    use HasFactory;

    protected $table = 'cpu_secretaria_matricula';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'nombre',
        'id_usuario',
        'id_sede',
        'casos_pendientes',
        'habilitada',
    ];

    public function sede()
    {
        return $this->belongsTo(CpuSede::class, 'id_sede');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'id_usuario');
    }
}
