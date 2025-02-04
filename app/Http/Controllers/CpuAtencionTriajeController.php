<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CpuAtencionTriaje;
use App\Models\CpuDerivacion;

class CpuAtencionTriajeController extends Controller
{
    public function obtenerTallaPesoPaciente($id_persona)
    {
        // Validar los parámetros de entrada
        // $request->validate([
        //     'id_paciente' => 'required|integer|exists:cpu_derivaciones,id_paciente'
        // ]);

        // Obtener el id_paciente de la solicitud
        $idPaciente = $id_persona;

        // Obtener la derivación más reciente del paciente
        $derivacion = CpuDerivacion::where('id_paciente', $idPaciente)->pluck('id');

        if ($derivacion->isEmpty()) {
            return response()->json(['mensaje' => 'No hay registros de derivación para el paciente'], 204);
        }

        // Obtener los datos de triaje más recientes correspondientes a la derivación
        $triaje = CpuAtencionTriaje::whereIn('id_derivacion', $derivacion)
            ->orderBy('updated_at', 'desc')
            ->first();

        if (!$triaje) {
            return response()->json(['mensaje' => 'No hay registros de datos de triaje para la derivación'], 204);
        }

        // Devolver los datos de talla y peso como respuesta JSON
        return response()->json([
            'id' => $triaje->id,
            'talla' => $triaje->talla,
            'peso' => $triaje->peso,
            'temperatura' => $triaje->peso,
            'saturacion' => $triaje->saturacion,
            'presion_sistolica' => $triaje->presion_sistolica,
            'presion_diastolica' => $triaje->presion_diastolica
        ]);
    }

    public function obtenerDatosTriajePorDerivacion(Request $request)
    {
        // Validar los parámetros de entrada
        $request->validate([
            'id_derivacion' => 'required|integer|exists:cpu_atenciones_triaje,id_derivacion'
        ]);

        // Obtener el id_derivacion de la solicitud
        $idDerivacion = $request->input('id_derivacion');

        // Obtener los datos de triaje correspondientes a la derivación
        $triaje = CpuAtencionTriaje::where('id_derivacion', $idDerivacion)->first();

        if (!$triaje) {
            return response()->json(['error' => 'Datos de triaje no encontrados para la derivación'], 404);
        }

        // Devolver los datos de triaje como respuesta JSON
        return response()->json([
            'id_derivacion' => $triaje->id_derivacion,
            'talla' => $triaje->talla,
            'peso' => $triaje->peso,
            'temperatura' => $triaje->temperatura,
            'saturacion' => $triaje->saturacion,
            'presion_sistolica' => $triaje->presion_sistolica,
            'presion_diastolica' => $triaje->presion_diastolica,
        ]);
    }
}
