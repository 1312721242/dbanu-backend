<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CpuTipoDiscapacidad extends Model
{
    use HasFactory;

    protected $table = 'cpu_tipos_discapacidad';

    protected $fillable = [
        'descripcion',
    ];
}
