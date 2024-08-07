<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CpuInsumo extends Model
{
    use HasFactory;

    protected $table = 'cpu_insumo';

    protected $fillable = [
        'id_tipo_insumo',
        'ins_descripcion',
        'ins_cantidad',
        'estado_insumo',
        'modo_adquirido',
        'num_documento',
        'nombre_proveedor',
        'fecha_recibido',
        'fecha_ingreso_sistema',
        'fecha_update',
        'codigo',
        'unidad_medida',
        'serie',
        'modelo',
        'marca',
        'cantidad_unidades',
    ];

    public $timestamps = false; // Si no estás utilizando las columnas de timestamps
}
