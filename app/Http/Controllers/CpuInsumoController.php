<?php

namespace App\Http\Controllers;

use App\Models\CpuInsumo;
use Illuminate\Http\Request;

class CpuInsumoController extends Controller
{
    public function getInsumos()
    {
        $insumosMedicos = CpuInsumo::where('id_tipo_insumo', '!=', 1)
            ->where('cantidad_unidades', '>=', 1)
            ->orderBy('ins_descripcion', 'asc')
            ->select('id', 'id_tipo_insumo', 'ins_descripcion', 'cantidad_unidades', 'ins_cantidad')
            ->get();

        $medicamentos = CpuInsumo::where('id_tipo_insumo', '=', 1)
            ->where('cantidad_unidades', '>=', 1)
            ->orderBy('ins_descripcion', 'asc')
            ->select('id', 'id_tipo_insumo', 'ins_descripcion', 'cantidad_unidades', 'ins_cantidad')
            ->get();

        return response()->json([
            'insumosMedicos' => $insumosMedicos,
            'medicamentos' => $medicamentos
        ]);
    }
}

