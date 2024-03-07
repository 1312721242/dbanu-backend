<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CpuNotificacionMatricula extends Model
{
    use HasFactory;

    protected $table = 'cpu_notificacion_matricula';
    protected $fillable = ['mensaje', 'created_at', 'updated_at', 'id_estado', 'titulo','id_legalizacion'];
}

