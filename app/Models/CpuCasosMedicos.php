<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CpuCasosMedicos extends Model
{
    use HasFactory;

    protected $table = 'cpu_casos';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'nombre_caso',
        'id_estado',
    ];
}
