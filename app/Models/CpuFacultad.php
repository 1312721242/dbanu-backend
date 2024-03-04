<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CpuFacultad extends Model
{
    use HasFactory;
    protected $table = 'cpu_facultad';
    
    public function legalizacionMatriculas()
    {
        return $this->hasMany(CpuLegalizacionMatricula::class, 'id_facultad');
    }

}
