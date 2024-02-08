<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserMenu extends Model
{
    use HasFactory;

    protected $table = 'cpu_usermenu';

    public function userFunctions()
    {
        return $this->hasMany(UserFunction::class, 'id_usermenu', 'id_usermenu');
    }
}
