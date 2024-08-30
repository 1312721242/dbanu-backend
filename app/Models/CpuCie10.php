<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CpuCie10 extends Model
{
    use HasFactory;

    protected $table = 'cpu_cie10';

    protected $fillable = [
        'cie10_x',
        'cie10',
        'descripcioncie',
    ];

    public $timestamps = false; // Si la tabla no tiene timestamps, indica que no se usarán.
}
