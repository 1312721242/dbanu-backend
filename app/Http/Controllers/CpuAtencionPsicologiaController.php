<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CpuAtencionPsicologia;
use App\Models\CpuAtencion;
use App\Models\CpuCasosPsicologia;
use App\Models\CpuDerivacion; // Asegúrate de importar este modelo
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class CpuAtencionPsicologiaController extends Controller
{
    public function store(Request $request)
    {
        // Validación de los campos generales
        $request->validate([
            'funcionarios' => 'required|integer',
            'id_persona' => 'required|integer',
            'tipo_usuario'=> 'required|string',
            'medio_atencion' => 'required|string',
            'tipo_atencion' => 'required|string',
            'motivo' => 'nullable|string',
            'evolucion' => 'nullable|string',
            'anio_atencion' => 'required|integer',
            'diagnostico' => 'nullable|string',
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

        // Crear o asociar el caso y la atención psicológica
        if ($request->activarcaso && in_array($request->tipo_atencion, ['INICIO'])) {
            $nuevoCaso = CpuCasosPsicologia::create([
                'nombre_caso' => $request->caso,
                'id_estado' => 8, // Estado inicial del caso
            ]);

            $cpuAtencion = CpuAtencion::create([
                'id_funcionario' => $request->funcionarios,
                'id_persona' => $request->id_persona,
                'via_atencion' => $request->medio_atencion,
                'motivo_atencion' => $request->motivo,
                'detalle_atencion' => $request->evolucion,
                'fecha_hora_atencion' => now(),
                'anio_atencion' => $request->anio_atencion,
                'id_caso' => $nuevoCaso->id,
            ]);
        } else {
            $cpuAtencion = CpuAtencion::create([
                'id_funcionario' => $request->funcionarios,
                'id_persona' => $request->id_persona,
                'via_atencion' => $request->medio_atencion,
                'motivo_atencion' => $request->motivo,
                'detalle_atencion' => $request->evolucion,
                'fecha_hora_atencion' => now(),
                'anio_atencion' => $request->anio_atencion,
                'id_caso' => $request->id_caso,
            ]);

            if ($request->input('altacaso') && $request->input('tipo_atencion') === 'SUBSECUENTE') {
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

        // Guardar datos de derivación si el switch de derivación está activo
        if ($request->input('derivacionFlag')) {
            $derivacionData = $request->validate([
                // Ya no necesitamos 'ate_id' en el request, usamos el generado
                'id_doctor_al_que_derivan' => 'required|integer|exists:users,id',
                'id_paciente' => 'required|integer|exists:cpu_personas,id',
                'motivo_derivacion' => 'required|string',
                'detalle_derivacion' => 'required|string',
                'id_area' => 'required|integer',
                'fecha_para_atencion' => 'required|date',
                'hora_para_atencion' => 'required|date_format:H:i:s',
                'id_estado_derivacion' => 'integer|exists:cpu_estados,id',
                'id_turno_asignado' => 'required|integer|exists:cpu_turnos,id_turnos',
            ]);

            // Usamos el id generado para ate_id
            $derivacionData['ate_id'] = $cpuAtencion->id;
            $derivacionData['id_funcionario_que_derivo'] = Auth::id();
            $derivacionData['fecha_derivacion'] = Carbon::now();

            $derivacion = CpuDerivacion::create($derivacionData);

            return response()->json($derivacion, 201);
        }

        return response()->json($atencionPsicologia, 201);
    }

    public function index()
    {
        $casos = CpuCasosPsicologia::all();
        return response()->json($casos);
    }
}
