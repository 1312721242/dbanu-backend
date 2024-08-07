<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CpuInsumoOcupado;
use Carbon\Carbon;

class CpuInsumoOcupadoController extends Controller
{
    // Función para agregar un nuevo insumo ocupado
    public function store(Request $request)
    {
        $data = $request->validate([
            'id_insumo' => 'required|integer|exists:cpu_insumo,id',
            'id_funcionario' => 'required|integer|exists:users,id',
            'cantidad_ocupado' => 'required|numeric',
            'id_paciente' => 'required|integer|exists:cpu_personas,id',
            'detalle_ocupado' => 'nullable|string',
            'fecha_uso' => 'required|date',
        ]);

        $insumoOcupado = CpuInsumoOcupado::create($data);

        return response()->json($insumoOcupado, 201);
    }

    // Función para consultar por rango de fechas
    public function getByDateRange(Request $request)
    {
        $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date',
        ]);

        $fechaInicio = Carbon::parse($request->input('fecha_inicio'))->startOfDay();
        $fechaFin = Carbon::parse($request->input('fecha_fin'))->endOfDay();

        $insumosOcupados = CpuInsumoOcupado::whereBetween('fecha_uso', [$fechaInicio, $fechaFin])->get();

        return response()->json($insumosOcupados);
    }

    // Función para consultar por funcionario
    public function getByFuncionario($id_funcionario)
    {
        $insumosOcupados = CpuInsumoOcupado::where('id_funcionario', $id_funcionario)->get();

        return response()->json($insumosOcupados);
    }

    // Función para consultar por paciente
    public function getByPaciente($id_paciente)
    {
        $insumosOcupados = CpuInsumoOcupado::where('id_paciente', $id_paciente)->get();

        return response()->json($insumosOcupados);
    }
}
