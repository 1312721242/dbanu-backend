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
    
            // Crear la atención general
            $atencion = CpuAtencion::create([
                'id_persona' => $request->atencion['id_persona'],
                'id_funcionario' => $request->atencion['id_funcionario'],
                'via_atencion' => $request->atencion['via_atencion'],
                'motivo_atencion' => $request->atencion['motivo_atencion'],
                'fecha_hora_atencion' => now(),
                'anio_atencion' => now()->year,
                'diagnostico' => json_encode($request->diagnostico),
            ]);
    
            // Si el id_diente existe, actualiza, sino inserta uno nuevo
            if (isset($request->odontograma['id_diente']) && !empty($request->odontograma['id_diente'])) {
                // Actualizar el registro del diente existente
                $cpuDientes = CpuDiente::where('id_diente', $request->odontograma['id_diente'])
                    ->update([
                        'arcada' => json_encode($request->odontograma['arcada']),
                    ]);
            } else {
                // Crear un nuevo registro en la tabla `cpu_dientes`
                $cpuDientes = CpuDiente::create([
                    'id_paciente' => $request->atencion['id_persona'],
                    'arcada' => json_encode($request->odontograma['arcada']),
                ]);
            }
    
            // Crear la atención odontológica específica
            $atencionOdontologica = CpuAtencionOdontologia::create([
                'id_cpu_atencion' => $atencion->id,
                'id_diente' => $cpuDientes->id_diente,
                'enfermedad_actual' => $request->atencion['enfermedad_proble_actual'],
                'examenes_estomatognatico' => json_encode($request->examen_estomatognatico),
                'planes' => json_encode($request->planes),
                'tratamiento' => json_encode($request->tratamientos),
            ]);
    
            DB::commit();
    
            return response()->json([
                'message' => 'Atención odontológica guardada con éxito',
                'atencion' => $atencion,
                'atencionOdontologica' => $atencionOdontologica,
                'cpuDientes' => $cpuDientes
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