<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CpuElementoFundamental extends Model
{
    use HasFactory;
    protected $table = 'cpu_elemento_fundamental';

    protected $fillable = [
        'id_estandar',
        'descripcion',
        'created_at',
        'update_at',
        'id_sede',
    ];


    public function estandar()
    {
        return $this->belongsTo(CpuEstandar::class, 'id_estandar');
    }
    public function evidencias()
    {
        return $this->hasMany(CpuEvidencia::class, 'id_elemento_fundamental');
    }
    public function sede()
    {
        return $this->belongsTo(CpuSede::class, 'id_sede');
    }

}
