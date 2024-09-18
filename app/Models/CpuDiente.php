<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CpuDiente extends Model
{
    use HasFactory;

    protected $table = 'cpu_dientes';

    protected $fillable = [
        'id_paciente',
        'arcada',
    ];

    protected $casts = [
        'arcada' => 'json',
    ];

    public function paciente()
    {
        return $this->belongsTo(CpuPersona::class, 'id_paciente');
    }
}
