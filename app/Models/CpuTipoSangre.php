<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CpuTipoSangre extends Model
{
    use HasFactory;

    protected $table = 'cpu_tipos_sangre';

    protected $fillable = ['descripcion'];

    public $timestamps = true;
}
