<?php

namespace App\Http\Controllers;

use App\Models\CpuAtencion;
use Illuminate\Http\Request;
use App\Models\CpuAtencionTriaje;
use App\Models\CpuDerivacion;
use Illuminate\Support\Facades\Log;

class CpuAtencionTriajeController extends Controller
{
    public function obtenerTallaPesoPaciente($id_paciente)
    {
        // Validar los parámetros de entrada
        // $request->validate([
        //     'id_paciente' => 'required|integer|exists:cpu_derivaciones,id_paciente'
        // ]);

        // Log::info('id_paciente recibido:', $request->all());

        // Obtener el id_paciente de la solicitud
        // $idPaciente = $request->input('id_paciente');

        // Buscar todas las atenciones del paciente ordenadas de forma descendente por ID
        $atenciones = CpuAtencion::where('id_persona', $id_paciente)
            ->orderBy('id', 'desc') // Ordenamos por ID en orden descendente (últimos registros primero)
            ->pluck('id'); // Obtenemos solo los IDs de las atenciones

        if ($atenciones->isEmpty()) {
            return response()->json(['error' => 'No se encontraron atenciones para el paciente'], 204);
        }

        // Iterar sobre los id_atencion en orden descendente y buscar el primer triaje disponible
        foreach ($atenciones as $idAtencion) {
            $triaje = CpuAtencionTriaje::where('id_atencion', $idAtencion)
                ->orderBy('updated_at', 'desc') // Ordenamos por la última actualización
                ->first();

            if ($triaje) {
                // Si encontramos un triaje, devolvemos los datos
                return response()->json([
                    'id' => $triaje->id,
                    'talla' => $triaje->talla,
                    'peso' => $triaje->peso,
                    'temperatura' => $triaje->temperatura,
                    'saturacion' => $triaje->saturacion,
                    'presion_sistolica' => $triaje->presion_sistolica,
                    'presion_diastolica' => $triaje->presion_diastolica
                ]);
            }
        }

        // Si no se encontró ningún triaje, devolver error
        return response()->json(['error' => 'No se encontraron datos de triaje para las atenciones registradas'], 204);
    }




    public function obtenerDatosTriajePorDerivacion(Request $request)
    {
        // Validar los parámetros de entrada
        $request->validate([
            'id_atencion' => 'required|integer|exists:cpu_atenciones_triaje,id_atencion'
        ]);

        // Obtener el id_derivacion de la solicitud
        $idAtencion = $request->input('id_atencion');

        // Obtener los datos de triaje correspondientes a la derivación
        $triaje = CpuAtencionTriaje::where('ate_id', $idAtencion)->first();

        if (!$triaje) {
            return response()->json(['error' => 'Datos de triaje no encontrados para la derivación'], 204);
        }

        // Devolver los datos de triaje como respuesta JSON
        return response()->json([
            'id_derivacion' => $triaje->id_atencion,
            'talla' => $triaje->talla,
            'peso' => $triaje->peso,
            'temperatura' => $triaje->temperatura,
            'saturacion' => $triaje->saturacion,
            'presion_sistolica' => $triaje->presion_sistolica,
            'presion_diastolica' => $triaje->presion_diastolica,
        ]);
    }
}
