<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NvDocente extends Model
{
    use HasFactory;

    protected $table = 'nv.docentes';
    protected $primaryKey = 'id';

    protected $fillable = [
        'identificacion','nombres','apellidos','correo','telefono',
        'titulo','dedicacion','activo','id_usuario'
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];
}
