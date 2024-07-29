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
            ->select(
                'periodo',
                'identifica',
                'apellidos',
                'nombres',
                'sede',
                'carrera',
                'tipo_evalu',
                'grupo_ca',
                'zona',
                'bloque',
                'sala',
                'fecha',
                'horario',
                'cod_lugar'
            )
            ->get();

        return response()->json($evaluaciones);
    }
    public function getEvaluacionesCedula(Request $request)
    {
        $query = CpuAspirantesEvaluaciones::query();

        if ($request->has('identifica') && $request->input('identifica') != '') {
            $query->where('identifica', 'like', '%' . $request->input('identifica') . '%');
        }

        if ($request->has('nombres') && $request->input('nombres') != '') {
            $query->where('nombres', 'like', '%' . $request->input('nombres') . '%');
        }

        if ($request->has('apellidos') && $request->input('apellidos') != '') {
            $query->where('apellidos', 'like', '%' . $request->input('apellidos') . '%');
        }

        $evaluaciones = $query->select(
            'periodo',
            'identifica',
            'apellidos',
            'nombres',
            'sede',
            'carrera',
            'tipo_evalu',
            'grupo_ca',
            'zona',
            'bloque',
            'sala',
            'fecha',
            'horario',
            'cod_lugar'
        )
            ->get();

        return response()->json($evaluaciones);
    }

    public function updateAsistencia(Request $request)
    {
        $request->validate([
            'identifica' => 'required|string|exists:cpu_aspirantes_evaluaciones,identifica',
            'asistencia' => 'required|boolean',
            'fecha_asistencia' => 'required|date'
        ]);

        $identifica = $request->input('identifica');
        $asistencia = $request->input('asistencia');
        $fecha_asistencia = $request->input('fecha_asistencia');

        $evaluacion = CpuAspirantesEvaluaciones::where('identifica', $identifica)->firstOrFail();
        $evaluacion->asistencia = $asistencia;
        $evaluacion->fecha_asistencia = $fecha_asistencia;
        $evaluacion->save();

        return response()->json(['message' => 'Asistencia actualizada correctamente']);
    }

}
