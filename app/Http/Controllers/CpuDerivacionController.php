<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CpuDerivacion;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class CpuDerivacionController extends Controller
{
    public function index()
    {
        $derivaciones = CpuDerivacion::all();
        return response()->json($derivaciones);
    }

    public function show($id)
    {
        $derivacion = CpuDerivacion::find($id);

        if (!$derivacion) {
            return response()->json(['error' => 'Derivación no encontrada'], 404);
        }

        return response()->json($derivacion);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'ate_id' => 'required|integer|exists:cpu_atenciones,id',
            'id_doctor_al_que_derivan' => 'required|integer|exists:users,id',
            'id_paciente' => 'required|integer|exists:cpu_personas,id',
            'motivo_derivacion' => 'required|string',
            'id_area' => 'required|integer',
            'fecha_para_atencion' => 'required|date',
            'hora_para_atencion' => 'required|date_format:H:i:s',
            'id_estado_derivacion' => 'integer|exists:cpu_estados,id',
        ]);

        $data['id_funcionario_que_derivo'] = Auth::id();
        $data['fecha_derivacion'] = Carbon::now();

        $derivacion = CpuDerivacion::create($data);

        return response()->json($derivacion, 201);
    }

    public function update(Request $request, $id)
    {
        $derivacion = CpuDerivacion::find($id);

        if (!$derivacion) {
            return response()->json(['error' => 'Derivación no encontrada'], 404);
        }

        $data = $request->validate([
            'ate_id' => 'integer|exists:cpu_atenciones,id',
            'id_doctor_al_que_derivan' => 'integer|exists:users,id',
            'id_paciente' => 'integer|exists:cpu_personas,id',
            'motivo_derivacion' => 'string',
            'id_area' => 'integer',
            'fecha_para_atencion' => 'date',
            'hora_para_atencion' => 'date_format:H:i:s',
            'id_estado_derivacion' => 'integer|exists:cpu_estados,id',
        ]);

        $derivacion->update($data);

        return response()->json($derivacion);
    }

    public function destroy($id)
    {
        $derivacion = CpuDerivacion::find($id);

        if (!$derivacion) {
            return response()->json(['error' => 'Derivación no encontrada'], 404);
        }

        $derivacion->delete();

        return response()->json(['success' => true]);
    }
}
