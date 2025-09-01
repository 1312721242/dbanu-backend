<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NvParalelo extends Model
{
    use HasFactory;

    protected $table = 'nv.paralelos';
    protected $primaryKey = 'id';

    protected $fillable = ['codigo','activo'];

    protected $casts = ['activo' => 'boolean'];
}
