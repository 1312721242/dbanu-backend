<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'usr_tipo',
        'usr_sede',
        'usr_profesion',
        'usr_facultad',
        'api_token',
        'usr_estado',
        'foto_perfil',
        'id_user_update'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    // Relación con CpuUserRole
    public function tipoUsuario()
    {
        return $this->belongsTo(CpuUserRole::class, 'usr_tipo', 'id_userrole');
    }

    // Relación con CpuSede
    public function sede()
    {
        return $this->belongsTo(CpuSede::class, 'usr_sede', 'id');
    }

    //relacion profesion
    public function profesion()
    {
        return $this->belongsTo(CpuProfesion::class, 'usr_profesion', 'id');
    }

}

