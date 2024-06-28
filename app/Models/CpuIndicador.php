<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CpuIndicador extends Model
{
    use HasFactory;
    protected $table = 'cpu_indicador'; // Nombre de la tabla en la base de datos
    protected $primaryKey = 'id'; // Nombre de la columna de clave primaria
    public $timestamps = false; // Indica que no hay columnas para timestamps (created_at, updated_at)

    protected $fillable = [
        'descripcion',
        'created_at',
        'update_at',
    ];
}
