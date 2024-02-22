<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CpuEstado extends Model
{
    use HasFactory;

    protected $table = 'cpu_estados'; // Nombre de la tabla en la base de datos

    protected $primaryKey = 'id'; // Nombre de la clave primaria

    protected $fillable = ['estado']; // Campos que se pueden asignar de forma masiva
}
