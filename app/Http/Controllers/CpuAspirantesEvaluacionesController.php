<?php

namespace App\Http\Controllers;

use App\Models\CpuAspirantesEvaluaciones;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

    public function getReporteEvaluaciones(Request $request)
    {
        $request->validate([
            'fecha_inicio' => 'required|date_format:Y-m-d',
            'fecha_fin' => 'required|date_format:Y-m-d',
        ]);

        $fechaInicio = $request->input('fecha_inicio');
        $fechaFin = $request->input('fecha_fin');

        $periodo = $request->input('periodo');
        $identifica = $request->input('identifica');

        // Consulta para obtener las evaluaciones segmentadas y contadas por rango de fecha
        $query = CpuAspirantesEvaluaciones::select(
            'sede',
            // 'carrera',
            // 'sexo',
            // 'genero',
            // 'zona',
            // 'bloque',
            // 'sala',
            // 'horario',
            DB::raw('SUM(CASE WHEN asistencia = 1 THEN 1 ELSE 0 END) AS asistentes'),
            DB::raw('SUM(CASE WHEN asistencia = 0 THEN 1 ELSE 0 END) AS ausentes')
        )
        ->whereBetween('fecha_formateada', [$fechaInicio, $fechaFin]);

        // Aplicar filtros opcionales si están presentes
        if ($periodo) {
            $query->where('periodo', $periodo);
        }

        if ($identifica) {
            $query->where('identifica', $identifica);
        }

        $evaluaciones = $query->groupBy(
            'sede',
            // 'carrera',
            // 'sexo',
            // 'genero',
            // 'zona',
            // 'bloque',
            // 'sala',
            // 'horario'
        )
        ->get();

        return response()->json($evaluaciones);
    }

}
