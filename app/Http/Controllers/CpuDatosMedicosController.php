<?php

namespace App\Http\Controllers;

use App\Models\CpuDatosMedicos;
use App\Models\CpuTipoSangre;
use Illuminate\Http\Request;

class CpuDatosMedicosController extends Controller
{
    public function index()
    {
        $datosMedicos = CpuDatosMedicos::all();
        return response()->json($datosMedicos);
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_persona' => 'required|exists:cpu_personas,id',
            'enfermedades_catastroficas' => 'required|boolean',
            'detalle_enfermedades' => 'nullable|json',
            'tipo_sangre' => 'required|exists:cpu_tipos_sangre,id',
            'tiene_seguro_medico' => 'required|boolean',
            'alergias' => 'required|boolean',
            'detalles_alergias' => 'nullable|json',
            'embarazada' => 'required|boolean',
            'meses_embarazo' => 'nullable|numeric',
            'observacion_embarazo' => 'nullable|string',
            'dependiente_medicamento' => 'required|boolean',
            'medicamentos_dependiente' => 'nullable|json',
        ]);

        $datosMedicos = CpuDatosMedicos::create($request->all());
        return response()->json($datosMedicos, 201);
    }

    public function show($id_persona)
    {
        $datosMedicos = CpuDatosMedicos::where('id_persona', $id_persona)->firstOrFail();
        return response()->json($datosMedicos);
    }

    public function update(Request $request, $id)
    {
        $datosMedicos = CpuDatosMedicos::findOrFail($id);

        $request->validate([
            'id_persona' => 'sometimes|required|exists:cpu_personas,id',
            'enfermedades_catastroficas' => 'sometimes|required|boolean',
            'detalle_enfermedades' => 'nullable|json',
            'tipo_sangre' => 'sometimes|required|exists:cpu_tipos_sangre,id',
            'tiene_seguro_medico' => 'sometimes|required|boolean',
            'alergias' => 'sometimes|required|boolean',
            'detalles_alergias' => 'nullable|json',
            'embarazada' => 'sometimes|required|boolean',
            'meses_embarazo' => 'nullable|numeric',
            'observacion_embarazo' => 'nullable|string',
            'dependiente_medicamento' => 'sometimes|required|boolean',
            'medicamentos_dependiente' => 'nullable|json',
        ]);

        $datosMedicos->update($request->all());
        return response()->json($datosMedicos);
    }

    public function destroy($id)
    {
        $datosMedicos = CpuDatosMedicos::findOrFail($id);
        $datosMedicos->delete();
        return response()->json(null, 204);
    }
}
