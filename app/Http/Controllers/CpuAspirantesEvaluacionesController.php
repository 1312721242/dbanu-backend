<?php

namespace App\Http\Controllers;

use App\Models\CpuAspirantesEvaluaciones;
use Illuminate\Http\Request;

class CpuAspirantesEvaluacionesController extends Controller
{
    public function getEvaluaciones(Request $request)
    {
        $request->validate([
            'periodo' => 'required|string',
            'identifica' => 'required|string',
        ]);

        $periodo = $request->input('periodo');
        $identifica = $request->input('identifica');

        $evaluaciones = CpuAspirantesEvaluaciones::where('periodo', $periodo)
            ->where('identifica', $identifica)
            ->select('periodo', 'identifica', 'apellidos', 'nombres', 'sede', 'carrera',
                     'tipo_evalu', 'grupo_ca', 'zona', 'bloque', 'sala', 'fecha', 'horario',
                     'cod_lugar')
            ->get();

        return response()->json($evaluaciones);
    }
}
