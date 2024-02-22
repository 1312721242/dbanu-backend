<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Userrolefunction extends Model
{
    use HasFactory;

    protected $table = 'cpu_userrolefunction';

    protected $fillable = [
        'id_userrole',
        'nombre',
        'accion',
        'id_menu',
        'id_usermenu',
        'created_at',
        'updated_at',
    ];

    protected $primaryKey = 'id_userrf';
}
