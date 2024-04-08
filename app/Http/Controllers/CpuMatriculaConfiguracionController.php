<?php

namespace App\Http\Controllers;

use App\Models\CpuMatriculaConfiguracion;
use Illuminate\Http\Request;

class CpuMatriculaConfiguracionController extends Controller
{
    // public function __construct()
    // {
    //     $this->middleware('auth:api');
    // }
    public function index()
    {
        return CpuMatriculaConfiguracion::all();
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'id' => 'sometimes|required|integer|exists:cpu_matricula_configuracion,id',
            'id_periodo' => 'required|integer',
            'id_estado' => 'required|integer',
            'fecha_inicio_matricula_ordinaria' => 'required|date',
            'fecha_fin_matricula_ordinaria' => 'required|date',
            'fecha_inicio_matricula_extraordinaria' => 'required|date',
            'fecha_fin_matricula_extraordinaria' => 'required|date',
            'fecha_inicio_habil_login' => 'required|date',
            'fecha_fin_habil_login' => 'required|date',
        ]);

        $configuracion = CpuMatriculaConfiguracion::updateOrCreate(
            ['id' => $request->id], // Keys to find
            $validatedData // Values to fill or update
        );

        return response()->json($configuracion, 200);
    }


    public function show($id)
    {
        return CpuMatriculaConfiguracion::findOrFail($id);
    }

    public function fechasMatricula($id_periodo)
    {
        // Devuelve todos los registros donde 'id_periodo' es igual al parámetro recibido
        return CpuMatriculaConfiguracion::where('id_periodo', $id_periodo)->get();
    }

    public function periodoActivo()
    {
        return CpuMatriculaConfiguracion::where('id_estado', 8)->get();
    }
}
