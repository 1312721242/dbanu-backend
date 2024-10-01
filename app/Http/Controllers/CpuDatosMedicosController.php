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
            'enfermedades_catastroficas' => 'nullable|boolean',
            'detalle_enfermedades' => 'nullable|json',
            'tipo_sangre' => 'nullable|exists:cpu_tipos_sangre,id',
            'tiene_seguro_medico' => 'nullable|boolean',
            'alergias' => 'nullable|boolean',
            'detalles_alergias' => 'nullable|json',
            'embarazada' => 'nullable|boolean',
            'meses_embarazo' => 'nullable|numeric',
            'observacion_embarazo' => 'nullable|string',
            'dependiente_medicamento' => 'nullable|boolean',
            'medicamentos_dependiente' => 'nullable|json',
        ]);

        $datosMedicos = CpuDatosMedicos::create($request->all());
        return response()->json($datosMedicos, 201);
    }

    public function show($id_persona)
    {
        $datosMedicos = CpuDatosMedicos::where('id_persona', $id_persona)->first();

        // Si no se encuentran datos, retornamos una respuesta vacía
        if (!$datosMedicos) {
            return response()->json(['message' => 'No se encontraron datos médicos'], 200);
        }

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
