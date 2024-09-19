<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CpuDiente;

class CpuDienteController extends Controller
{
    public function buscarPorPaciente($id_paciente)
    {
        $diente = CpuDiente::where('id_paciente', $id_paciente)->first();

        if (!$diente) {
            return response()->json(['message' => 'No se encontraron datos para este paciente'], 404);
        }

        $arcada = $diente->arcada;

        if (!isset($arcada['adulto'])) {
            return response()->json(['message' => 'La estructura de datos no es válida'], 400);
        }

        $respuesta = [
            'id_paciente' => $diente->id_paciente,
            'arcada' => [
                'adulto' => $arcada['adulto']
            ]
        ];

        return response()->json($respuesta);
    }
}
