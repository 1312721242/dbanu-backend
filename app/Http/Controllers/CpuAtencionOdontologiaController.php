<?php

namespace App\Http\Controllers;

use App\Models\CpuAtencionOdontologia;
use App\Models\CpuAtencion;
use App\Models\CpuDiente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CpuAtencionOdontologiaController extends Controller
{
    public function store(Request $request)
    {
        try {
            DB::beginTransaction();

            // Validar los datos del request, permitiendo que los campos de diagnóstico y otros sean opcionales
            $validator = Validator::make($request->all(), [
                'atencion.id_persona' => 'required|exists:cpu_personas,id',
                'atencion.motivo_atencion' => 'required|string',
                'odontograma' => 'required|array',
                'odontograma.adulto' => 'required|array',
                'diagnostico' => 'nullable|array', // Ahora permite que diagnostico sea opcional y de tipo array
                'examen_estomatognatico' => 'nullable|array', // Permite examen_estomatognatico como opcional
                'tratamientos' => 'nullable|array|min:0', // Permite tratamientos como opcional y acepta array vacío
                'planes' => 'nullable|array|min:0', // Permite planes como opcional y acepta array vacío
            ]);

            // Validación de fallos
            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Error en la validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Crear la atención general
            $atencion = CpuAtencion::create([
                'id_persona' => $request->atencion['id_persona'],
                'id_funcionario' => $request->atencion['id_funcionario'], // Solo almacenar el id_funcionario
                'via_atencion' => $request->atencion['via_atencion'],
                'motivo_atencion' => $request->atencion['motivo_atencion'],
                'fecha_hora_atencion' => now(),
                'anio_atencion' => now()->year,
                'diagnostico' => !empty($request->diagnostico) ? json_encode($request->diagnostico) : null,
                'id_estado' =>1,
            ]);

            // Manejar el registro del odontograma (adultos)
            $arcada = ['adulto' => $request->odontograma['adulto']];

            // Verificar si el paciente ya tiene un registro de dientes
            $cpuDiente = CpuDiente::where('id_paciente', $request->atencion['id_persona'])->first();

            if ($cpuDiente) {
                // Si ya existe, actualizar el registro
                $cpuDiente->update([
                    'arcada' => $arcada, // Guardar la estructura de arcada como un array
                    
                ]);
            } else {
                // Si no existe, crear un nuevo registro de dientes
                $cpuDiente = CpuDiente::create([
                    'id_paciente' => $request->atencion['id_persona'],
                    'arcada' => $arcada, // Guardar la estructura de arcada como un array
                ]);
            }

            // Crear la atención odontológica específica
            $atencionOdontologica = CpuAtencionOdontologia::create([
                'id_cpu_atencion' => $atencion->id,
                'id_diente' => $cpuDiente->id, // Relacionar el diente existente o creado
                'enfermedad_actual' => $request->atencion['enfermedad_proble_actual'],
                'examenes_estomatognatico' => !empty($request->examen_estomatognatico) ? json_encode($request->examen_estomatognatico) : null,
                'planes' => !empty($request->planes) ? json_encode($request->planes) : null,
                'tratamiento' => !empty($request->tratamientos) ? json_encode($request->tratamientos) : null,
            ]);

            DB::commit();
            return response()->json(['message' => 'Atención guardada con éxito'], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al guardar la atención odontológica',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ], 500);
        }
    }
}
