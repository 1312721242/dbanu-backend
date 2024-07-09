<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CpuTipoComida extends Model
{
    use HasFactory;

    protected $table = 'cpu_tipo_comida';

    protected $fillable = [
        'descripcion',
    ];

    public $timestamps = false;
}
