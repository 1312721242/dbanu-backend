<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CpuAtencionPsicologia;
use App\Models\CpuAtencion; // Asegúrate de importar este modelo

class CpuAtencionPsicologiaController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'id_funcionario' => 'required|integer',
            'id_persona' => 'required|integer',
            'tipo_usuario'=> 'required|string',
            'medio_atencion' => 'required|string',
            'tipo_atencion' => 'required|string',
            'motivo' => 'required|string',
            'evolucion' => 'required|string',
            'anio_atencion' => 'required|integer',
            // Valida el resto de campos específicos para AtencionPsicologia
            'diagnostico' => 'required|string',
            'referido' => 'required|string',
            'naturaleza' => 'required|string',
            'acciones_afirmativas' => 'required|string',
            'consumo_sustancias' => 'required|string',
            'frecuencia_consumo' => 'required|string',
            'detalles_complementarios' => 'required|string',
            'aspecto_actitud_presentacion' => 'required|string',
            'aspecto_clinico' => 'required|string',
            'sensopercepcion' => 'required|string',
            'memoria' => 'required|string',
            'ideacion' => 'required|string',
            'pensamiento' => 'required|string',
            'lenguaje' => 'required|string',
            'juicio' => 'required|string',
            'afectividad' => 'required|string',
            'voluntad' => 'required|string',
        ]);

        // Crear la atención principal en la tabla cpu_atenciones
        $cpuAtencion = CpuAtencion::create([
            'id_funcionario' => $request->id_funcionario,
            'id_persona' => $request->id_persona,
            'via_atencion' => $request->medio_atencion,
            'motivo_atencion' => $request->motivo,
            'detalle_atencion' => $request->evolucion,
            'fecha_hora_atencion' => now(),
            'anio_atencion' => $request->anio_atencion,
        ]);

        // Crear la atención de psicología con el ID obtenido
        $atencionPsicologia = CpuAtencionPsicologia::create([
            'id_cpu_atencion' => $cpuAtencion->id,
            'tipo_usuario' => $request->tipo_usuario,
            'tipo_atencion' => $request->tipo_atencion,
            'medio_atencion' => $request->medio_atencion,
            'motivo_consulta' => $request->motivo,
            'evolucion' => $request->evolucion,
            'diagnostico' => $request->diagnostico,
            'referido' => $request->referido,
            'naturaleza' => $request->naturaleza,
            'acciones_afirmativas' => $request->acciones_afirmativas,
            'consumo_sustancias' => $request->consumo_sustancias,
            'frecuencia_consumo' => $request->frecuencia_consumo,
            'detalles_complementarios' => $request->detalles_complementarios,
            'aspecto_actitud_presentacion' => $request->aspecto_actitud_presentacion,
            'aspecto_clinico' => $request->aspecto_clinico,
            'sensopercepcion' => $request->sensopercepcion,
            'memoria' => $request->memoria,
            'ideacion' => $request->ideacion,
            'pensamiento' => $request->pensamiento,
            'lenguaje' => $request->lenguaje,
            'juicio' => $request->juicio,
            'afectividad' => $request->afectividad,
            'voluntad' => $request->voluntad,
        ]);

        return response()->json($atencionPsicologia, 201);
    }
}
