<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CpuAtencion;
use App\Models\CpuAtencionNutricion;
use App\Models\CpuAtencionTriaje;
use App\Models\CpuCasosMedicos;
use App\Models\CpuDerivacion;
use App\Models\CpuTurno;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB; // Asegúrate de importar esta clase
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CpuAtencionesController extends Controller
{
    public function guardarAtencion(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_funcionario' => 'required|integer',
            'id_persona' => 'required|integer',
            'via_atencion' => 'required|string',
            'motivo_atencion' => 'required|string',
            // 'detalle_atencion' => 'required|string',
            'fecha_hora_atencion' => 'required|date_format:Y-m-d H:i:s',
            'anio_atencion' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $atencion = new CpuAtencion();
        $atencion->id_funcionario = $request->input('id_funcionario');
        $atencion->id_persona = $request->input('id_persona');
        $atencion->via_atencion = $request->input('via_atencion');
        $atencion->motivo_atencion = $request->input('motivo_atencion');
        $atencion->detalle_atencion = $request->input('detalle_atencion');
        $atencion->fecha_hora_atencion = $request->input('fecha_hora_atencion');
        $atencion->anio_atencion = $request->input('anio_atencion');

        // Campos opcionales
        $atencion->id_caso = $request->input('id_caso', null);
        $atencion->id_tipo_usuario = $request->input('id_tipo_usuario', null);
        $atencion->evolucion_enfermedad = $request->input('evolucion_enfermedad', '');
        $atencion->diagnostico = $request->input('diagnostico', '');
        $atencion->prescripcion = $request->input('prescripcion', '');
        $atencion->recomendacion = $request->input('recomendacion', '');

        $atencion->save();

        return response()->json(['success' => true, 'id' => $atencion->id]);
    }


    // // Consulta de las atenciones
    // public function obtenerAtencionesPorPaciente($id_persona, $id_funcionario)
    // {
    //     // Realiza la consulta filtrando por id_persona y id_funcionario y selecciona todas las columnas necesarias
    //     $atenciones = DB::table('cpu_atenciones as at')
    //         ->select(
    //             'at.id',
    //             'at.id_funcionario',
    //             'at.id_persona',
    //             'at.via_atencion',
    //             'at.motivo_atencion',
    //             'at.fecha_hora_atencion',
    //             'at.anio_atencion',
    //             'at.created_at',
    //             'at.updated_at',
    //             'at.detalle_atencion'
    //         )
    //         ->when($id_persona, function ($query, $id_persona) {
    //             return $query->where('at.id_persona', $id_persona);
    //         })
    //         ->when($id_funcionario, function ($query, $id_funcionario) {
    //             return $query->where('at.id_funcionario', $id_funcionario);
    //         })
    //         // Ordena por fecha de creación de forma descendente
    //         ->orderBy('at.created_at', 'desc')
    //         ->get();

    //     // Formatear la fecha después de obtener los datos
    //     $atenciones->transform(function ($atencion) {
    //         $atencion->fecha_hora_atencion = Carbon::parse($atencion->fecha_hora_atencion)->translatedFormat('l, d F Y');
    //         return $atencion;
    //     });

    //     // Log information
    //     Log::info('Atenciones obtenidas:', ['atenciones' => $atenciones]);

    //     // Retorna la respuesta en formato JSON
    //     return response()->json($atenciones);
    // }
    public function obtenerAtencionesPorPaciente($id_persona, $id_funcionario)
        {
            // Realiza la consulta filtrando por id_persona y id_funcionario y selecciona todas las columnas necesarias
            $atenciones = DB::table('cpu_atenciones as at')
                ->select(
                    'at.id',
                    'at.id_funcionario',
                    'at.id_persona',
                    'at.via_atencion',
                    'at.motivo_atencion',
                    'at.fecha_hora_atencion',
                    'at.anio_atencion',
                    'at.created_at',
                    'at.updated_at',
                    'at.detalle_atencion',
                    'at.id_caso',
                    'at.id_tipo_usuario',
                    'at.evolucion_enfermedad',
                    'at.diagnostico',
                    'at.prescripcion',
                    'at.recomendacion',
                    'at.tipo_atencion'
                )
                ->when($id_persona, function ($query, $id_persona) {
                    return $query->where('at.id_persona', $id_persona);
                })
                ->when($id_funcionario, function ($query, $id_funcionario) {
                    return $query->where('at.id_funcionario', $id_funcionario);
                })
                ->orderBy('at.created_at', 'desc')
                ->get();

            $atenciones->transform(function ($atencion) {
                // Determinar si la atención es parte de un caso
                if ($atencion->id_caso) {
                    $atencion->tipo = "Caso";

                    // Obtener el caso y todas las atenciones relacionadas
                    $caso = DB::table('cpu_casos as c')
                        ->join('cpu_estados as e', 'c.id_estado', '=', 'e.id')
                        ->select('c.id', 'c.nombre_caso', 'e.id as id_estado', 'e.estado as estado')
                        ->where('c.id', $atencion->id_caso)
                        ->first();

                    if ($caso) {
                        $atencion->nombre_principal = $caso->nombre_caso; // Usar el nombre del caso como nombre principal
                        $atencion->caso = [
                            'id' => $caso->id,
                            'nombre_caso' => $caso->nombre_caso,
                            'estado' => $caso->id_estado == 8 ? 'Abierto' : 'Cerrado',
                        ];

                        // Obtener todas las atenciones relacionadas con el caso
                        $atencionesRelacionadas = DB::table('cpu_atenciones as at')
                            ->where('at.id_caso', $caso->id)
                            ->orderBy('at.created_at', 'desc')
                            ->get();

                        // Transformar las fechas en atenciones relacionadas
                        $atencionesRelacionadas->transform(function ($atRelacionada) {
                            $atRelacionada->fecha_hora_atencion = Carbon::parse($atRelacionada->fecha_hora_atencion)
                                ->translatedFormat('l, d F Y');
                            return $atRelacionada;
                        });

                        // Añadir las atenciones relacionadas al objeto de respuesta
                        $atencion->atenciones_relacionadas = $atencionesRelacionadas;
                    }
                } else {
                    $atencion->tipo = "Atención";
                    $atencion->nombre_principal = $atencion->motivo_atencion; // Usar el motivo de la atención como nombre principal
                }

                // Formatear la fecha de la atención principal
                $atencion->fecha_hora_atencion = Carbon::parse($atencion->fecha_hora_atencion)
                    ->translatedFormat('l, d F Y');
                return $atencion;
            });

            // Log de la información
            Log::info('Atenciones obtenidas:', ['atenciones' => $atenciones]);

            // Retorna la respuesta en formato JSON
            return response()->json($atenciones);
        }


    public function eliminarAtencion($atencionId)
    {
        try {
            DB::table('cpu_atenciones')->where('id', $atencionId)->delete();
            return response()->json(['message' => 'Atención eliminada correctamente']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al eliminar la atención'], 500);
        }
    }

    public function obtenerUltimaConsulta($usr_tipo, $id_persona, $id_caso)
    {
        try {
            // Busca la última atención del paciente con el id_persona e id_caso especificados
            $ultimaConsulta = CpuAtencion::where('id_persona', $id_persona)
                ->where('id_funcionario', $usr_tipo)
                ->where('id_caso', $id_caso)
                ->orderBy('fecha_hora_atencion', 'desc')
                ->first();

            // Si se encuentra una consulta
            if ($ultimaConsulta) {
                // Formatear la fecha para mostrar el día de la semana y el nombre completo del mes en español
                $ultimaConsulta->fecha_hora_atencion = Carbon::parse($ultimaConsulta->fecha_hora_atencion)->translatedFormat('l, d F Y');
            } else {
                return response()->json(['mensaje' => 'No se encontraron consultas para el paciente con el caso especificado'], 404);
            }

            // Devuelve la última consulta encontrada
            return response()->json($ultimaConsulta, 200);
        } catch (\Exception $e) {
            // Maneja cualquier error que ocurra durante la ejecución
            return response()->json(['error' => 'Error al obtener la última consulta: ' . $e->getMessage()], 500);
        }
    }

    public function guardarAtencionConTriaje(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_funcionario' => 'required|integer',
            'id_paciente' => 'required|integer',
            'id_derivacion' => 'required|integer|exists:cpu_derivaciones,id',
            'id_estado_derivacion' => 'required|integer|exists:cpu_derivaciones,id_estado_derivacion',
            'talla' => 'required|integer',
            'peso' => 'required|numeric',
            'temperatura' => 'required|numeric',
            'presion_sistolica' => 'required|integer',
            'presion_diastolica' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        // Iniciar una transacción
        DB::beginTransaction();

        try {
            // Actualizar la derivación
            $derivacion = CpuDerivacion::findOrFail($request->input('id_derivacion'));
            $derivacion->id_estado_derivacion = 18;
            $derivacion->save();

            // Guardar la atención
            $atencion = new CpuAtencion();
            $atencion->id_funcionario = $request->input('id_funcionario');
            $atencion->id_persona = $request->input('id_paciente');
            $atencion->via_atencion = 'Presencial';
            $atencion->motivo_atencion = 'Triaje';
            $atencion->detalle_atencion = 'Signos Vitales';
            $atencion->fecha_hora_atencion = Carbon::now();
            $atencion->anio_atencion = Carbon::now()->year;
            $atencion->save();

            // Guardar el triaje
            $triaje = new CpuAtencionTriaje();
            $triaje->id_derivacion = $request->input('id_derivacion');
            $triaje->talla = $request->input('talla');
            $triaje->peso = $request->input('peso');
            $triaje->temperatura = $request->input('temperatura');
            $triaje->presion_sistolica = $request->input('presion_sistolica');
            $triaje->presion_diastolica = $request->input('presion_diastolica');
            $triaje->save();

            // Confirmar la transacción
            DB::commit();

            return response()->json(['success' => true, 'atencion_id' => $atencion->id, 'triaje_id' => $triaje->id]);
        } catch (\Exception $e) {
            // Deshacer la transacción en caso de error
            DB::rollBack();
            Log::error('Error al guardar la atención y el triaje:', ['exception' => $e->getMessage()]);
            return response()->json(['error' => 'Error al guardar la atención y el triaje'], 500);
        }
    }

    public function guardarAtencionNutricion(Request $request)
    {
        // Validar los campos
        $validator = Validator::make($request->all(), [
            'id_funcionario' => 'required|integer',
            'id_paciente' => 'required|integer',
            'id_derivacion' => 'required|integer|exists:cpu_derivaciones,id',
            'talla' => 'required|numeric',
            'peso' => 'required|numeric',
            'temperatura' => 'required|numeric',
            'presion_sistolica' => 'required|numeric',
            'presion_diastolica' => 'required|numeric',
            'imc' => 'nullable|numeric',
            'peso_ideal' => 'nullable|numeric',
            'estado_paciente' => 'nullable|string|max:50',
            'antecedente_medico' => 'nullable|string',
            'motivo' => 'nullable|string',
            'patologia' => 'nullable|string',
            'alergias' => 'nullable|json',
            'intolerancias' => 'nullable|json',
            'nombre_plan_nutricional' => 'nullable|string|max:255',
            'plan_nutricional' => 'nullable|json',
            'permitidos' => 'nullable|json',
            'no_permitidos' => 'nullable|json',
            'recomendaciones' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        DB::beginTransaction();

        try {
            // Actualizar la derivación
            $derivacion = CpuDerivacion::findOrFail($request->input('id_derivacion'));
            $derivacion->id_estado_derivacion = 2;
            $derivacion->save();

            // Actualizar el estado del turno relacionado
            $turno = CpuTurno::findOrFail($derivacion->id_turno_asignado);
            $turno->estado = 2; // Actualiza el estado del turno a 2
            $turno->save();

            // Guardar la atención
            $atencion = new CpuAtencion();
            $atencion->id_funcionario = $request->input('id_funcionario');
            $atencion->id_persona = $request->input('id_paciente');
            $atencion->via_atencion = $request->input('via_atencion');
            $atencion->motivo_atencion = $request->input('motivo_atencion');
            $atencion->id_tipo_usuario = $request->input('id_tipo_usuario');
            $atencion->detalle_atencion = 'Atención Nutrición';
            $atencion->fecha_hora_atencion = Carbon::now();
            $atencion->anio_atencion = Carbon::now()->year;
            $atencion->recomendacion = $request->input('recomendaciones');
            $atencion->tipo_atencion = $request->input('tipo_atencion');
            $atencion->save();

            // Guardar la atención nutricional
            $nutricion = new CpuAtencionNutricion();
            $nutricion->id_derivacion = $request->input('id_derivacion');
            $nutricion->talla = $request->input('talla');
            $nutricion->peso = $request->input('peso');
            $nutricion->temperatura = $request->input('temperatura');
            $nutricion->presion_sistolica = $request->input('presion_sistolica');
            $nutricion->presion_diastolica = $request->input('presion_diastolica');
            $nutricion->imc = $request->input('imc');
            $nutricion->peso_ideal = $request->input('peso_ideal');
            $nutricion->estado_paciente = $request->input('estado_paciente');
            $nutricion->antecedente_medico = $request->input('antecedente_medico');
            $nutricion->patologia = $request->input('patologia');
            $nutricion->alergias = json_decode($request->input('alergias'), true);
            $nutricion->intolerancias = json_decode($request->input('intolerancias'), true);
            $nutricion->nombre_plan_nutricional = $request->input('nombre_plan_nutricional');
            $nutricion->plan_nutricional = json_decode($request->input('plan_nutricional'), true);
            $nutricion->permitidos = json_decode($request->input('permitidos'), true);
            $nutricion->no_permitidos = json_decode($request->input('no_permitidos'), true);
            $nutricion->save();

            // Guardar caso (si existe id_estado)
            if ($request->has('id_estado')) {
                $caso = new CpuCasosMedicos();
                $caso->nombre_caso = $request->input('nombre_plan_nutricional');
                $caso->id_estado = $request->input('id_estado');
                $caso->save();
            }

            DB::commit();

            return response()->json(['success' => true, 'nutricion_id' => $nutricion->id]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al guardar la atención nutricional:', ['exception' => $e->getMessage()]);
            return response()->json(['error' => 'Error al guardar la atención nutricional'], 500);
        }
    }
}
