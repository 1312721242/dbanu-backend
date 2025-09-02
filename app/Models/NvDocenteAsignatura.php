<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NvDocenteAsignatura extends Model
{
    use HasFactory;

    protected $table = 'nv.docente_asignatura';
    protected $primaryKey = 'id';

    protected $fillable = [
        'id_docente','id_asignatura','id_periodo','id_paralelo','horas','activo'
    ];

    protected $casts = ['activo'=>'boolean','horas'=>'integer'];
}
