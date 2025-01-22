<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CpuCargo extends Model
{
    use HasFactory;
    protected $table = 'cpu_cargo';
    protected $primaryKey = 'id_cargo';
    public $timestamps = false;
}
