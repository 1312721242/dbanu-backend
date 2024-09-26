<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CpuDiente;
use Illuminate\Support\Facades\Log;
class CpuDienteController extends Controller
{
    public function buscarPorPaciente($id_paciente)
{
    // Buscar el diente por el id del paciente
    $diente = CpuDiente::where('id_paciente', $id_paciente)->first();

    // Si no se encuentra el diente, devolver una respuesta indicando que no hay datos
    if (!$diente) {
        return response()->json([
            'message' => 'No se encontraron datos para este paciente',
            'arcada' => [
                'adulto' => [],
                'infantil' => []
            ]
        ], 404);
    }

    // Dado que Eloquent ya castea automáticamente a array, no necesitamos json_decode aquí
    $arcada = $diente->arcada; // Eloquent ya lo convierte a array, no uses json_decode

    // Preparar la respuesta con los datos del paciente
    $respuesta = [
        'id_paciente' => $diente->id_paciente,
        'id_diente' => $diente->id,
        'arcada' => [
            'adulto' => $arcada['adulto'] ?? [],
            'infantil' => $arcada['infantil'] ?? []
        ]
    ];

    return response()->json($respuesta, 200);
}

public function actualizarDiente(Request $request, $id_diente)
{
    // Buscar el diente por su ID
    $diente = CpuDiente::find($id_diente);

    if (!$diente) {
        return response()->json(['message' => 'Diente no encontrado'], 404);
    }

    // Aquí ya no necesitas json_encode, porque se guardará como un array en la columna jsonb
    $diente->arcada = $request->input('arcada');
    $diente->save();

    return response()->json(['message' => 'Diente actualizado con éxito']);
}

}
