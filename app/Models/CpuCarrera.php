<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CpuCarrera extends Model
{
    use HasFactory;
    protected $table = 'cpu_carrera';

    public function legalizacionMatriculas()
    {
        return $this->hasMany(CpuLegalizacionMatricula::class, 'id_carrera','id_sede');
    }

}
