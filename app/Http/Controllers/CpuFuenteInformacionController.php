<?php

namespace App\Http\Controllers;

use App\Models\CpuFuenteInformacion;
use App\Models\CpuIndicador;
use Illuminate\Http\Request;

class CpuFuenteInformacionController extends Controller
{
    public function getFuenteInformacion($id_indicador)
    {
        // Consulta las fuentes basadas en el id_indicador
        $fuentes = CpuFuenteInformacion::where('id_indicador', $id_indicador)->get();

        return response()->json($fuentes);
    }

    public function storeFuenteInformacion(Request $request)
    {
        // Validar los datos recibidos
        $validatedData = $request->validate([
            'id_indicador' => 'required|exists:cpu_indicador,id',
            'descripcion' => 'required|string',
        ]);

        // Crear la nueva fuente de información
        $fuente = CpuFuenteInformacion::create([
            'id_indicador' => $validatedData['id_indicador'],
            'descripcion' => $validatedData['descripcion'],
        ]);

        // Retornar una respuesta en formato JSON
        return response()->json([
            'message' => 'Fuente de información creada exitosamente.',
            'data' => $fuente,
        ], 201);
    }

    public function updateFuenteInformacion(Request $request, $id)
    {
        // Validar los datos recibidos
        $validatedData = $request->validate([
            'id_indicador' => 'required|exists:cpu_indicador,id',
            'descripcion' => 'required|string',
        ]);

        // Buscar la fuente de información por ID
        $fuente = CpuFuenteInformacion::find($id);

        if (!$fuente) {
            return response()->json([
                'message' => 'Fuente de información no encontrada.',
            ], 404);
        }

        // Actualizar la fuente de información
        $fuente->id_indicador = $validatedData['id_indicador'];
        $fuente->descripcion = $validatedData['descripcion'];
        $fuente->save();

        // Retornar una respuesta en formato JSON
        return response()->json([
            'message' => 'Fuente de información actualizada exitosamente.',
            'data' => $fuente,
        ], 200);
    }
}
