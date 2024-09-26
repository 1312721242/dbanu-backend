<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CpuDiente extends Model
{
    protected $fillable = [
        'id_paciente',
        'arcada',
    ];

    protected $casts = [
        'arcada' => 'array',
    ];
}

