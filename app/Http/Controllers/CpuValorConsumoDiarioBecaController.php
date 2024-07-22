<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CpuValorConsumoDiarioBeca;

class CpuValorConsumoDiarioBecaController extends Controller
{
    public function consultar()
    {
        $registro = CpuValorConsumoDiarioBeca::first();
        return response()->json($registro);
    }

    public function editar(Request $request)
    {
        $registro = CpuValorConsumoDiarioBeca::first();
        if ($registro) {
            $registro->update($request->only('valor'));
        } else {
            $registro = CpuValorConsumoDiarioBeca::create($request->only('valor'));
        }
        return response()->json($registro);
    }
}
