<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CpuAtencionPsicologia;
use App\Models\CpuAtencion;
use App\Models\CpuCasosPsicologia; // Asegúrate de importar este modelo

class CpuAtencionPsicologiaController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'usr_tipo' => 'required|integer',
            'id_persona' => 'required|integer',
            'tipo_usuario'=> 'required|string',
            'medio_atencion' => 'required|string',
            'tipo_atencion' => 'required|string',
            'motivo' => 'nullable|string',
            'evolucion' => 'nullable|string',
            'anio_atencion' => 'required|integer',
            'diagnostico' => 'nullable |string',
            'referido' => 'nullable|string',
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
            'caso' => 'nullable|string',
            'evolucion_caso' => 'nullable|string',
            'abordaje' => 'nullable|string',
            'observacion' => 'nullable|string',
            'descripcionfinal' => 'nullable|string',
            
        ]);

        // Verifica si el switch activarcaso está activo y el tipo de atención es INICIAL DE TRATAMIENTO o VALORACIÓN
        if ($request->activarcaso && in_array($request->tipo_atencion, ['INICIO'])) {
            // Crear un nuevo caso en la tabla cpu_casos
            $nuevoCaso = CpuCasosPsicologia::create([
                'nombre_caso' => $request->caso, // Puedes ajustar el nombre del caso según tus necesidades
                'id_estado' => 8, // Estado inicial del caso
            ]);

            // Guardar el nuevo caso en cpu_atenciones con el nuevo id_caso
            $cpuAtencion = CpuAtencion::create([
                'id_funcionario' => $request->usr_tipo,
                'id_persona' => $request->id_persona,
                'via_atencion' => $request->medio_atencion,
                'motivo_atencion' => $request->motivo,
                'detalle_atencion' => $request->evolucion,
                'fecha_hora_atencion' => now(),
                'anio_atencion' => $request->anio_atencion,
                'id_caso' => $nuevoCaso->id, // Asociar el nuevo caso creado
            ]);

        } else {
            // Si es subsecuente o fin de tratamiento, usa el caso existente
            $cpuAtencion = CpuAtencion::create([
                'id_funcionario' => $request->usr_tipo,
                'id_persona' => $request->id_persona,
                'via_atencion' => $request->medio_atencion,
                'motivo_atencion' => $request->evolucion,
                'detalle_atencion' => $request->evolucion,
                'fecha_hora_atencion' => now(),
                'anio_atencion' => $request->anio_atencion,
                'id_caso' => $request->id_caso, // Usa el caso proporcionado
            ]);

            // Si el tipo de atención es FIN DE TRATAMIENTO, actualiza el estado del caso
            if ($request->input('altacaso') && $request->input('tipo_atencion') === 'SUBSECUENTE') {            // if ($request->tipo_atencion == 'FINCASO') {
                CpuCasosPsicologia::where('id', $request->id_caso)->update(['id_estado' => 9]);
            }
            if ($request->tipo_atencion == 'REAPERTURA') {
                CpuCasosPsicologia::where('id', $request->id_caso)->update(['id_estado' => 8]);
            }

        }

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
            'evolucion_caso' => $request->evolucion,
            'abordaje_caso' => $request->abordaje,
            'prescripcion' => $request->observacion,
            'descripcionfinal' => $request->descripcionfinal,
        ]);

        return response()->json($atencionPsicologia, 201);
    }

    public function index()
    {
        $casos = CpuCasosPsicologia::all();
        return response()->json($casos);
    }
}
