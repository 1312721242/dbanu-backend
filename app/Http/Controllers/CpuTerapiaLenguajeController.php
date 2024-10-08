<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CpuAtencionesTerapiaLenguaje;
use App\Models\CpuAtencion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CpuTerapiaLenguajeController extends Controller
{
    /**
     * Guardar una nueva consulta de terapia de lenguaje.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function guardarConsultaTerapia(Request $request)
{
    DB::beginTransaction();

    try {
        // Validar los datos requeridos
        $validatedData = $request->validate([
            'id_funcionario' => 'required|integer',
            'id_persona' => 'required|integer',
            'medio_atencion' => 'nullable|string',
            'motivo_atencion' => 'required|string',
            'fecha_hora_atencion' => 'nullable|date',
            'anio_atencion' => 'nullable|integer',
            'detalle_atencion' => 'nullable|string',
            'tipo_usuario' => 'nullable|integer',
            'evolucion_enfermedad' => 'nullable|string',
            'diagnostico' => 'nullable|json',
            'prescripcion' => 'nullable|string',
            'recomendacion' => 'nullable|string',
            'tipo_atencion' => 'nullable|string',
            'id_cie10' => 'nullable|integer',
            'id_estado' => 'nullable|integer',
            'id_persona_padre' => 'nullable|integer',
            'id_persona_madre' => 'nullable|integer',
            'numero_hermanos' => 'nullable|integer',
            'antecedentes_embarazo' => 'nullable|array',
            'antecende_parto_nacido' => 'nullable|array',
            'antecedente_morbico' => 'nullable|array',
            'desarollo_psicomotor_lenguaje' => 'nullable|array',
            'mecanismo_oral_periferico' => 'nullable|array',
            'desarrollo_familiar' => 'nullable|array',
            'derivacion_externa' => 'nullable|string',
        ]);

        // Crear una nueva instancia de CpuAtencion
        $atencion = new CpuAtencion();
        $atencion->id_funcionario = $validatedData['id_funcionario'];
        $atencion->id_persona = $validatedData['id_persona'];
        $atencion->via_atencion = $validatedData['medio_atencion'] ?? null;
        $atencion->motivo_atencion = $validatedData['motivo_atencion'] ?? null;
        $atencion->fecha_hora_atencion = $validatedData['fecha_hora_atencion'] ?? now();
        $atencion->anio_atencion = $validatedData['anio_atencion'] ?? date('Y');
        $atencion->detalle_atencion = $validatedData['detalle_atencion'] ?? null;
        $atencion->id_tipo_usuario = $validatedData['tipo_usuario'] ?? null;
        $atencion->evolucion_enfermedad = $validatedData['evolucion_enfermedad'] ?? null;
        $atencion->diagnostico = json_encode($validatedData['diagnostico'] ?? []);
        $atencion->prescripcion = $validatedData['prescripcion'] ?? null;
        $atencion->recomendacion = $validatedData['recomendacion'] ?? null;
        $atencion->tipo_atencion = $validatedData['tipo_atencion'] ?? null;
        $atencion->id_cie10 = $validatedData['id_cie10'] ?? null;
        $atencion->id_estado = $validatedData['id_estado'] ?? 1; // Valor por defecto

        // Guardar la atención
        $atencion->save();

        // Crear una nueva instancia de CpuAtencionesTerapiaLenguaje
        $terapiaLenguaje = new CpuAtencionesTerapiaLenguaje();
        $terapiaLenguaje->id_atencion = $atencion->id;
        $terapiaLenguaje->id_persona_padre = $validatedData['id_persona_padre'] ?? null;
        $terapiaLenguaje->id_persona_madre = $validatedData['id_persona_madre'] ?? null;
        $terapiaLenguaje->numero_hermanos = $validatedData['numero_hermanos'] ?? null;

        // Convertir arrays a cadenas JSON
        $terapiaLenguaje->antecedentes_embarazo = json_encode($validatedData['antecedentes_embarazo'] ?? []);
        $terapiaLenguaje->antecende_parto_nacido = json_encode($validatedData['antecende_parto_nacido'] ?? []);
        $terapiaLenguaje->antecedente_morbico = json_encode($validatedData['antecedente_morbico'] ?? []);
        $terapiaLenguaje->desarollo_psicomotor_lenguaje = json_encode($validatedData['desarollo_psicomotor_lenguaje'] ?? []);
        $terapiaLenguaje->mecanismo_oral_periferico = json_encode($validatedData['mecanismo_oral_periferico'] ?? []);
        $terapiaLenguaje->desarrollo_familiar = json_encode($validatedData['desarrollo_familiar'] ?? []);

        $terapiaLenguaje->derivacion_externa = $validatedData['derivacion_externa'] ?? null;

        // Guardar datos específicos de Terapia de Lenguaje
        $terapiaLenguaje->save();

        // Confirmar la transacción
        DB::commit();

        return response()->json([
            'message' => 'Consulta de terapia de lenguaje guardada correctamente',
            'atencion_id' => $atencion->id,
            'terapia_lenguaje_id' => $terapiaLenguaje->id
        ], 201);

    } catch (\Exception $e) {
        // Si hay un error, deshacer la transacción
        DB::rollBack();
        Log::error('Error al guardar la consulta de terapia de lenguaje: ' . $e->getMessage());
        return response()->json(['error' => 'Error al guardar la consulta de terapia de lenguaje: ' . $e->getMessage()], 500);
    }
}

}
