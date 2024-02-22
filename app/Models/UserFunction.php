<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserFunction extends Model
{
    use HasFactory;

    // Nombre de la tabla en la base de datos
    protected $table = 'cpu_userfunction';
    protected $primaryKey = 'id_userfunction';

    protected $fillable = [
        'id_users',
        'id_usermenu',
        'id_userrole',
        'nombre',
        'accion',
        'id_menu',
    ];

    public function userMenu()
    {
        return $this->belongsTo(UserMenu::class, 'id_usermenu', 'id_usermenu');
    }
}
