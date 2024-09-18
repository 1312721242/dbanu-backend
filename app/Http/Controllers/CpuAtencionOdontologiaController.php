<?php

namespace App\Http\Controllers;

use App\Models\CpuAtencionOdontologia;
use App\Models\CpuAtencion;
use App\Models\CpuDientes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CpuAtencionOdontologiaController extends Controller
{
    public function store(Request $request)
    {
        try {
            DB::beginTransaction();

            // Primero, crear la atención general
            $atencion = CpuAtencion::create([
                'id_persona' => $request->id_paciente,
                'id_funcionario' => $request->id_funcionario,
                'via_atencion' => $request->via_atencion,
                'motivo_atencion' => $request->motivo_atencion,
                'fecha_hora_atencion' => now(),
                'anio_atencion' => now()->year,
                'diagnostico' => json_encode($request->diagnostico),
            ]);

            // Luego, crear la atención odontológica específica
            $atencionOdontologica = CpuAtencionOdontologia::create([
                'id_cpu_atencion' => $atencion->id,
                'id_diente' => $request->id_diente,
                'enfermedad_actual' => $request->enfermedad_actual,
                'examenes_estomatognatico' => json_encode($request->examenes_estomatognatico),
                'planes' => json_encode($request->planes),
                'tratamiento' => json_encode($request->tratamiento),
            ]);

            // Guardar o actualizar el odontograma
            $cpuDientes = CpuDientes::updateOrCreate(
                ['id_paciente' => $request->id_paciente],
                ['arcada' => json_encode($request->odontograma)]
            );

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