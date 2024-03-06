<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CpuCasosMatricula extends Model
{
    use HasFactory;

    protected $table = 'cpu_casos_matricula';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'id_legalizacion_matricula',
        'id_secretaria',
        'id_estado',
    ];

    public function legalizacionMatricula()
    {
        return $this->belongsTo(CpuLegalizacionMatricula::class, 'id_legalizacion_matricula');
    }

    public function secretaria()
    {
        return $this->belongsTo(CpuSecretariaMatricula::class, 'id_secretaria');
    }

    public function estado()
    {
        return $this->belongsTo(CpuEstados::class, 'id_estado');
    }

    public function carrera()
    {
        return $this->belongsTo(CpuCarrera::class, 'id_carrera');
    }

    public function sede()
    {
        return $this->belongsTo(CpuSede::class, 'id_sede');
    }

}
