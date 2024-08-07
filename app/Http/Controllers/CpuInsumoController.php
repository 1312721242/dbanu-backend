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
            ->get();

        $medicamentos = CpuInsumo::where('id_tipo_insumo', '=', 1)
            ->where('cantidad_unidades', '>=', 1)
            ->orderBy('ins_descripcion', 'asc')
            ->get();

        return response()->json([
            'insumosMedicos' => $insumosMedicos,
            'medicamentos' => $medicamentos
        ]);
    }
}

