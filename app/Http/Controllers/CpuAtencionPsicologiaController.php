<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AtencionPsicologia;
use App\Models\CpuAtencion; // Asegúrate de importar este modelo

class CpuAtencionPsicologiaController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'id_funcionario' => 'required|integer',
            'id_persona' => 'required|integer',
            'via_atencion' => 'required|string',
            'motivo' => 'required|string',
            'evolucion' => 'required|string',
            'anio_atencion' => 'required|integer',
            // Valida el resto de campos específicos para AtencionPsicologia
            'diagnostico' => 'required|string',
            'referido' => 'required|string',
            'naturaleza' => 'required|string',
            'acciones_afirmativas' => 'required|json',
            'consumo_sustancias' => 'required|json',
            'frecuencia_consumo' => 'required|json',
            'detalles_complementarios' => 'required|json',
            'aspecto_actitud_presentacion' => 'required|json',
            'aspecto_clinico' => 'required|json',
            'sensopercepcion' => 'required|json',
            'memoria' => 'required|json',
            'ideacion' => 'required|json',
            'pensamiento' => 'required|json',
            'lenguaje' => 'required|json',
            'juicio' => 'required|json',
            'afectividad' => 'required|json',
            'voluntad' => 'required|json',
        ]);

        // Crear la atención principal en la tabla cpu_atenciones
        $cpuAtencion = CpuAtencion::create([
            'id_funcionario' => $request->id_funcionario,
            'id_persona' => $request->id_persona,
            'via_atencion' => $request->via_atencion,
            'motivo_atencion' => $request->motivo,
            'detalle_atencion' => $request->evolucion,
            'fecha_hora_atencion' => now(),
            'anio_atencion' => $request->anio_atencion,
        ]);

        // Crear la atención de psicología con el ID obtenido
        $atencionPsicologia = AtencionPsicologia::create([
            'id_cpu_atencion' => $cpuAtencion->id,
            'motivo' => $request->motivo,
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
