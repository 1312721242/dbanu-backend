<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CpuPeriodos extends Model
{
    use HasFactory;

    protected $table = 'cpu_periodo'; // Nombre de la tabla en la base de datos

    protected $primaryKey = 'id'; // Nombre de la clave primaria

    protected $fillable = ['nombre']; // Campos que se pueden asignar de forma masiva
}
