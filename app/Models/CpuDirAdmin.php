<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CpuDirAdmin extends Model
{
    use HasFactory;
    protected $table = 'cpu_dir_admin';
    protected $primaryKey = 'id_dir_admin';
    public $timestamps = false;
}
