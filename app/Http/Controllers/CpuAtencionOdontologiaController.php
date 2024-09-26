<?php

namespace App\Http\Controllers;

use App\Models\CpuAtencionOdontologia;
use App\Models\CpuAtencion;
use App\Models\CpuDiente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CpuAtencionOdontologiaController extends Controller
{
    public function store(Request $request)
    {
        try {
            DB::beginTransaction();
    
            // Validar que los campos necesarios estén presentes
            if (!isset($request->atencion['id_persona'], $request->atencion['id_funcionario'], $request->odontograma['arcada'])) {
                return response()->json(['message' => 'Datos insuficientes para procesar la atención'], 422);
            }

            // Crear la atención general
            $atencion = CpuAtencion::create([
                'id_persona' => $request->atencion['id_persona'],
                'id_funcionario' => $request->atencion['id_funcionario'],
                'via_atencion' => $request->atencion['via_atencion'],
                'motivo_atencion' => $request->atencion['motivo_atencion'],
                'fecha_hora_atencion' => now(),
                'anio_atencion' => now()->year,
                'diagnostico' => json_encode($request->diagnostico),
                'id_estado' =>1,
            ]);

            // Inicializamos un array para guardar todas las atenciones odontológicas creadas
            $atencionesOdontologicas = [];

            // Iterar sobre los dientes de la arcada (tanto adulto como infantil)
            $arcada = $request->odontograma['arcada']; // Aquí están los dientes

            foreach (['adulto', 'infantil'] as $tipo) {
                if (isset($arcada[$tipo])) {
                    foreach ($arcada[$tipo] as $diente) {
                        // Verificar si el id_diente existe
                        if (isset($diente['id']) && !empty($diente['id'])) {
                            // Buscar y actualizar el registro del diente existente
                            $cpuDiente = CpuDiente::find($diente['id']);
                            if ($cpuDiente) {
                                $cpuDiente->arcada = json_encode($diente['faces']);
                                $cpuDiente->save();  // Guardar los cambios
                            } else {
                                return response()->json(['message' => 'Diente no encontrado'], 404);
                            }
                        } else {
                            // Crear un nuevo registro en la tabla `cpu_dientes`
                            $cpuDiente = CpuDiente::create([
                                'id_paciente' => $request->atencion['id_persona'],
                                'arcada' => json_encode($diente['faces']),
                            ]);
                        }

                        // Crear la atención odontológica específica para cada diente
                        $atencionOdontologica = CpuAtencionOdontologia::create([
                            'id_cpu_atencion' => $atencion->id,
                            'id_diente' => $cpuDiente->id,  // Relacionar la atención con el diente
                            'enfermedad_actual' => $request->atencion['enfermedad_proble_actual'] ?? null,
                            'examenes_estomatognatico' => json_encode($request->examen_estomatognatico ?? []),
                            'planes' => json_encode($request->planes ?? []),
                            'tratamiento' => json_encode($request->tratamientos ?? []),
                        ]);

                        // Agregar la atención odontológica creada al array
                        $atencionesOdontologicas[] = $atencionOdontologica;
                    }
                }
            }
    
            DB::commit();
    
            return response()->json([
                'message' => 'Atención odontológica guardada con éxito',
                'atencion' => $atencion,
                'atencionesOdontologicas' => $atencionesOdontologicas, // Devolver todas las atenciones creadas
                'cpuDiente' => $cpuDiente // Regresando el último diente procesado
            ], 201);
    
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al guardar la atención odontológica',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
