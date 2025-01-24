<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CpuTipoBeca extends Model
{
    use HasFactory;
    protected $table = 'cpu_tipo_becas';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'nombre_beca',
        'nivel',
        'id_estado',
        'created_at',
        'updated_at'
    ];

    public function estado()
    {
        return $this->belongsTo(CpuEstado::class, 'id_estado');
    }
}
