<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NvAsignatura extends Model
{
    use HasFactory;

    protected $table = 'nv.asignaturas';
    protected $primaryKey = 'id';

    protected $fillable = [
        'codigo','nombre','creditos','horas_semanales','tipo','activo','id_usuario'
    ];

    protected $casts = [
        'creditos' => 'integer',
        'horas_semanales' => 'integer',
        'activo' => 'boolean',
    ];
}
