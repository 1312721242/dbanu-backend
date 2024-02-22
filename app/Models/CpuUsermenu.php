<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CpuUsermenu extends Model
{
    use HasFactory;

    protected $table = 'cpu_usermenu'; // Nombre de la tabla
    protected $primaryKey = 'id_usermenu'; // Nombre de la clave primaria

    protected $fillable = [
        'menu',
        'icono',
    ];

    // public $timestamps = true;
}
