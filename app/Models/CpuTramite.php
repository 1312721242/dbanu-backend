<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CpuTramite extends Model
{
    use HasFactory;
    protected $table = 'cpu_tramites';
    protected $primaryKey = 'id_tramite';
    public $timestamps = true;

    protected $fillable = [
        'id_tramite',
        'tra_num_tramite',
        'tra_person_recibe',
        'tra_tipo',
        'tra_fecha_recibido',
        'tra_fecha_documento',
        'tra_num_documento',
        'tra_suscrito',
        'tra_direccion',
        'tra_asunto',
        'tra_area_derivada',
        'tra_fecha_derivacion',
        'tra_fecha_contestacion',
        'tra_num_contestacion',
        'tra_direccion_enviada',
        'tra_observacion',
        'tra_estado_tramite',
        'tra_link_receptado',
        'tra_link_enviado',
        'tra_cargo',
        'tra_id_persona_modifico',
        'created_at',
        'updated_at',
    ];
}
