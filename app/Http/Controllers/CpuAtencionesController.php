<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CpuAtencion;
use App\Models\CpuAtencionFisioterapia;
use App\Models\CpuAtencionMedicinaGeneral;
use App\Models\CpuAtencionNutricion;
use App\Models\CpuAtencionTriaje;
use App\Models\CpuCasosMedicos;
use App\Models\CpuDerivacion;
use App\Models\CpuInsumo;
use App\Models\CpuInsumoOcupado;
use App\Models\CpuTurno;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB; // Asegúrate de importar esta clase
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use App\Models\CpuEstado;
use Mpdf\Tag\B;
use Illuminate\Support\Facades\Cache;

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

        // Auditoría
        $this->auditar('cpu_atencion', 'id', '', $atencion->id, 'INSERCION', "INSERCION DE NUEVA ATENCION: {$atencion->id},
                                                                 PACIENTE: {$atencion->id_persona},
                                                                 FUNCIONARIO: {$atencion->id_funcionario},
                                                                 VIA DE ATENCION: {$atencion->via_atencion},
                                                                 MOTIVO DE ATENCION: {$atencion->motivo_atencion},
                                                                 FECHA Y HORA DE ATENCION: {$atencion->fecha_hora_atencion},
                                                                 ANIO DE ATENCION: {$atencion->anio_atencion}", $request);

        // Emite el evento
        // Broadcast(new TriajeActualizado($atencion))->toOthers();

        return response()->json(['success' => true, 'id' => $atencion->id]);
    }


    // // Consulta de traer las atenciones de cada usuario en la tabla de la opcion de regitros
    public function obtenerAtencionesPorPaciente($id_persona, $id_funcionario, $usr_tipo = null)
    {
        // Realiza la consulta filtrando por id_persona, id_funcionario e id_estado = 1 y selecciona todas las columnas necesarias
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
                DB::raw('at.diagnostico::text as diagnostico'), // Convertir el campo jsonb a texto
                'at.prescripcion',
                'at.recomendacion',
                'at.tipo_atencion',
                'at.id_estado',
                // Seleccionar columnas adicionales de la tabla cpu_atenciones_psicologia
                'ap.tipo_usuario as ap_tipo_usuario',
                'ap.tipo_atencion as ap_tipo_atencion',
                'ap.medio_atencion as ap_medio_atencion',
                'ap.motivo_consulta as ap_motivo_consulta',
                'ap.evolucion as ap_evolucion',
                'ap.diagnostico as ap_diagnostico_psicologico',
                'ap.referido as ap_referido',
                'ap.observacion as ap_observacion',
                'ap.acciones_afirmativas as ap_acciones_afirmativas',
                'ap.consumo_sustancias as ap_consumo_sustancias',
                'ap.frecuencia_consumo as ap_frecuencia_consumo',
                'ap.detalles_complementarios as ap_detalles_complementarios',
                'ap.aspecto_actitud_presentacion as ap_aspecto_actitud_presentacion',
                'ap.aspecto_clinico as ap_aspecto_clinico',
                'ap.sensopercepcion as ap_sensopercepcion',
                'ap.memoria as ap_memoria',
                'ap.ideacion as ap_ideacion',
                'ap.pensamiento as ap_pensamiento',
                'ap.lenguaje as ap_lenguaje',
                'ap.juicio as ap_juicio',
                'ap.afectividad as ap_afectividad',
                'ap.voluntad as ap_voluntad',
                'ap.evolucion_caso as ap_evolucion_caso',
                'ap.abordaje_caso as ap_abordaje_caso',
                'ap.prescripcion as ap_prescripcion_psicologica',
                'ap.descripcionfinal as ap_descripcionfinal',
                // Datos adicionales de otras tablas
                'ts.tipo_informe as ts_tipo_informe',
                'ts.requiriente as ts_requiriente',
                'ts.numero_tramite as ts_numero_tramite',
                'ts.detalle_general as ts_detalle_general',
                'ts.observaciones as ts_observaciones',
                'ts.url_informe as ts_url_informe',
                'ts.periodo as ts_periodo'
            )
            ->leftJoin('cpu_atenciones_trabajo_social as ts', function ($join) use ($usr_tipo) {
                $join->on('at.id', '=', 'ts.id_atenciones');
                if ($usr_tipo !== null && $usr_tipo == 10) {
                    $join->whereNotNull('ts.id'); // Solo incluye los registros si usr_tipo es 10
                }
            })
            ->leftJoin('cpu_atenciones_psicologia as ap', 'at.id', '=', 'ap.id_cpu_atencion')  // Hacer el join con cpu_atenciones_psicologia
            ->when($id_persona, function ($query, $id_persona) {
                return $query->where('at.id_persona', $id_persona);
            })
            ->when($id_funcionario, function ($query, $id_funcionario) {
                return $query->where('at.id_funcionario', $id_funcionario);
            })
            ->where('at.id_estado', 1)  // Añadir la condición de que id_estado sea igual a 1
            ->orderBy('at.created_at', 'desc')
            ->get();

        // Filtramos las atenciones que tienen id_caso y las que no
        $atencionesConCaso = $atenciones->filter(function ($atencion) {
            return !is_null($atencion->id_caso);
        })->groupBy('id_caso');

        // No agrupamos las atenciones que no tienen id_caso
        $atencionesSinCaso = $atenciones->filter(function ($atencion) {
            return is_null($atencion->id_caso);
        });

        // Mapeamos las atenciones con caso
        $resultadoConCaso = $atencionesConCaso->map(function ($atencionesPorCaso, $id_caso) {
            $atencionPrincipal = $atencionesPorCaso->first();
            $atencionPrincipal->tipo = "Caso";

            // Obtener los detalles del caso
            $caso = DB::table('cpu_casos as c')
                ->join('cpu_estados as e', 'c.id_estado', '=', 'e.id')
                ->select('c.id', 'c.nombre_caso', 'e.id as id_estado', 'e.estado as estado')
                ->where('c.id', $id_caso)
                ->first();

            if ($caso) {
                $atencionPrincipal->nombre_principal = $caso->nombre_caso;
                $atencionPrincipal->caso = [
                    'id' => $caso->id,
                    'nombre_caso' => $caso->nombre_caso,
                    'estado' => $caso->id_estado == 8 ? 'Abierto' : 'Cerrado',
                ];

                // Obtener todas las atenciones relacionadas con el caso
                $atencionesRelacionadas = DB::table('cpu_atenciones as at')
                    ->leftJoin('cpu_atenciones_psicologia as ap', 'at.id', '=', 'ap.id_cpu_atencion')
                    ->where('at.id_caso', $caso->id)
                    ->where('at.id_estado', 1)
                    ->orderBy('at.created_at', 'desc')
                    ->select(
                        'at.id',
                        'at.id_funcionario',
                        'at.id_persona',
                        'at.via_atencion',
                        'at.motivo_atencion',
                        'at.fecha_hora_atencion',
                        'at.anio_atencion',
                        'at.created_at as at_created_at',
                        'at.updated_at as at_updated_at',
                        'at.detalle_atencion',
                        'at.id_caso',
                        'at.id_tipo_usuario',
                        'at.evolucion_enfermedad',
                        DB::raw('at.diagnostico::text as at_diagnostico'), // Convertir el campo jsonb a texto
                        'at.prescripcion as at_prescripcion',
                        'at.recomendacion',
                        'at.tipo_atencion',
                        // Datos de la tabla cpu_atenciones_psicologia
                        'ap.observacion as ap_observacion',
                        'ap.evolucion_caso as ap_evolucion_caso',
                        'ap.abordaje_caso as ap_abordaje_caso',
                        'ap.prescripcion as ap_prescripcion_psicologica',
                        'ap.descripcionfinal as ap_descripcionfinal'
                    )
                    ->get();

                // Formatear las fechas en atenciones relacionadas
                $atencionesRelacionadas->transform(function ($atRelacionada) {
                    if (!$atRelacionada->fecha_hora_atencion instanceof Carbon) {
                        $atRelacionada->fecha_hora_atencion = Carbon::parse($atRelacionada->fecha_hora_atencion);
                    }
                    $atRelacionada->fecha_hora_atencion = $atRelacionada->fecha_hora_atencion
                        ->translatedFormat('l, d F Y');
                    return $atRelacionada;
                });

                $atencionPrincipal->atenciones_relacionadas = $atencionesRelacionadas;
            }

            if (!$atencionPrincipal->fecha_hora_atencion instanceof Carbon) {
                $atencionPrincipal->fecha_hora_atencion = Carbon::parse($atencionPrincipal->fecha_hora_atencion);
            }
            $atencionPrincipal->fecha_hora_atencion = $atencionPrincipal->fecha_hora_atencion
                ->translatedFormat('l, d F Y');

            return $atencionPrincipal;
        });

        // Mapeamos las atenciones sin caso individualmente
        $resultadoSinCaso = $atencionesSinCaso->map(function ($atencion) {
            $atencion->tipo = "Atención";
            $atencion->nombre_principal = $atencion->motivo_atencion;

            if (!$atencion->fecha_hora_atencion instanceof Carbon) {
                $atencion->fecha_hora_atencion = Carbon::parse($atencion->fecha_hora_atencion);
            }
            $atencion->fecha_hora_atencion = $atencion->fecha_hora_atencion
                ->translatedFormat('l, d F Y');

            return $atencion;
        });

        // Combina ambos resultados, asegurando que las atenciones sin caso no se pierdan
        $resultado = $resultadoSinCaso->merge($resultadoConCaso)->values();

        // Log de la información
        Log::info('Atenciones obtenidas:', ['atenciones' => $resultado]);

        // Procesar la URL del informe
        $resultado->transform(function ($atencion) {
            if (isset($atencion->ts_url_informe)) {
                $baseUrl = URL::to('/');
                $atencion->ts_url_informe = $baseUrl . '/Files/' . $atencion->ts_url_informe;
            }
            return $atencion;
        });

        // Verificar si ya se ha registrado una auditoría reciente para evitar duplicados
        $cacheKey = 'auditoria_cpu_atencion_id_persona_' . $id_persona;
        if (!Cache::has($cacheKey)) {
            // Auditoría
            $this->auditar('cpu_atencion', 'id_persona', '', $id_persona, 'CONSULTA', "CONSULTA DE ATENCIONES POR PACIENTE: {$id_persona}");
            // Almacenar en caché por un corto periodo de tiempo
            Cache::put($cacheKey, true, now()->addSeconds(10));
        }

        // Retorna la respuesta en formato JSON
        return response()->json($resultado);
    }

    public function eliminarAtencion($atencionId, $nuevoEstado)
    {
        $atencion = CpuAtencion::find($atencionId);
        if ($atencion) {
            $atencion->id_estado = $nuevoEstado;  // Asignar el nuevo estado desde el parámetro de la URL
            $atencion->save();

            // Auditoría
            $this->auditar('cpu_atencion', 'id_estado', '', $nuevoEstado, 'ELIMINACION', "ELIMINACION DE ATENCION: {$atencionId}");

            return response()->json(['message' => 'Estado actualizado correctamente'], 200);
        }
        return response()->json(['message' => 'Atención no encontrada'], 404);
    }

    public function obtenerUltimaConsulta($area_atencion, $usr_tipo, $id_persona, $id_caso)
    {
        try {
            // Registra el área de atención en el log
            Log::info('Área de atención: ' . $area_atencion);

            // Busca la última atención del paciente
            $ultimaConsulta = CpuAtencion::where('id_persona', $id_persona)
                ->where('id_funcionario', $usr_tipo)
                ->where('id_caso', $id_caso)
                ->orderBy('fecha_hora_atencion', 'desc')
                ->first();

            if (!$ultimaConsulta) {
                return response()->json(['mensaje' => 'No se encontraron consultas para el paciente con el caso especificado'], 404);
            }

            // Formatea la fecha
            $ultimaConsulta->fecha_hora_atencion = Carbon::parse($ultimaConsulta->fecha_hora_atencion)->translatedFormat('l, d F Y');

            // Incluye el diagnóstico
            $ultimaConsulta->diagnostico = $ultimaConsulta->diagnostico ?? 'Sin diagnóstico';

            // Obtener el id_derivacion
            Log::info('ID de la última consulta: ' . $ultimaConsulta->id);
            $derivacion = CpuDerivacion::where('ate_id', $ultimaConsulta->id)->first();
            $ultimaConsulta->id_derivacion = $derivacion ? $derivacion->id : null;

            $respuesta = $ultimaConsulta->toArray();

            // if (strtoupper($area_atencion) === "NUTRICIÓN") {
            //     $atencionNutricion = CpuAtencionNutricion::where('id_derivacion', $ultimaConsulta->id)->first();

            //     Log::info("Datos de CpuAtencionNutricion: ", $atencionNutricion ? $atencionNutricion->toArray() : ['Sin datos']);

            //     if ($atencionNutricion) {
            //         $respuesta['datos_nutricion'] = $atencionNutricion->toArray();
            //     }
            // }

            // Ahora buscar en `cpu_atencion_nutricion` por `id_atencion`, NO por `id_derivacion`
            if (strtoupper($area_atencion) === "NUTRICIÓN") {
                $atencionNutricion = CpuAtencionNutricion::where('id_atencion', $ultimaConsulta->id)->first();

                // 🔍 Agregar LOG para ver lo que devuelve CpuAtencionNutricion
                Log::info("Datos de CpuAtencionNutricion: ", $atencionNutricion ? $atencionNutricion->toArray() : ['Sin datos']);

                if ($atencionNutricion) {
                    $respuesta['datos_nutricion'] = $atencionNutricion->toArray();
                }
            }

            if (strtoupper($area_atencion) === "FISIOTERAPIA") {
                $atencionFisioterapia = CpuAtencionFisioterapia::where('id_atencion', $ultimaConsulta->id)->first();

                // 🔍 Agregar LOG para ver lo que devuelve CpuAtencionNutricion
                Log::info("Datos de CpuAtencionFisioterapia: ", $atencionFisioterapia ? $atencionFisioterapia->toArray() : ['Sin datos']);

                if ($atencionFisioterapia) {
                    $respuesta['datos_fisioterapia'] = $atencionFisioterapia->toArray();
                }
            }

            // Verificar si ya se ha registrado una auditoría reciente para evitar duplicados
            $cacheKey = 'auditoria_cpu_atencion_ultima_consulta_' . $id_persona;
            if (!Cache::has($cacheKey)) {
                // Auditoría
                $this->auditar('cpu_atencion', 'id_persona', '', $id_persona, 'CONSULTA', "CONSULTA DE ULTIMA CONSULTA: {$id_persona}");
                // Almacenar en caché por un corto periodo de tiempo
                Cache::put($cacheKey, true, now()->addSeconds(10));
            }

            return response()->json($respuesta, 200);
        } catch (\Exception $e) {
            Log::error('Error al obtener la última consulta: ' . $e->getMessage());
            return response()->json(['error' => 'Error al obtener la última consulta: ' . $e->getMessage()], 500);
        }
    }


    public function cambiarEstado(Request $request)
    {
        $estado = CpuEstado::find($request->id);
        $estado->activo = !$estado->activo;
        $estado->save();

        // Auditoría
        $this->auditar('cpu_estado', 'activo', '', $estado->activo, 'MODIFICACION', "MODIFICACION DE ESTADO: {$request->id}");

        // broadcast(new EstadoCambiado($estado));

        return response()->json(['message' => 'Estado actualizado']);
    }


    public function guardarAtencionConTriaje(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_funcionario' => 'required|integer',
            'id_paciente' => 'required|integer',
            'id_atencion' => 'required|integer|exists:cpu_atenciones,id',
            'id_estado_derivacion' => 'required|integer|exists:cpu_derivaciones,id_estado_derivacion',
            'talla' => 'required|integer',
            'peso' => 'required|numeric',
            'temperatura' => 'required|numeric',
            'saturacion' => 'required|numeric',
            'presion_sistolica' => 'required|integer',
            'presion_diastolica' => 'required|integer',
            'imc' => 'required|string',
            'pesoIdeal' => 'required|string',
            'estadoPaciente' => 'required|string',
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
            $triaje->id_atencion = $request->input('id_atencion');
            $triaje->talla = $request->input('talla');
            $triaje->peso = $request->input('peso');
            $triaje->temperatura = $request->input('temperatura');
            $triaje->saturacion = $request->input('saturacion');
            $triaje->presion_sistolica = $request->input('presion_sistolica');
            $triaje->presion_diastolica = $request->input('presion_diastolica');
            $triaje->imc = $request->input('imc');
            $triaje->peso_ideal = $request->input('pesoIdeal');
            $triaje->estado_paciente = $request->input('estadoPaciente');
            $triaje->save();

            // 🔥 Emitir evento de WebSockets
            // broadcast(new NuevaAtencionGuardada($atencion))->toOthers();

            // Confirmar la transacción
            DB::commit();

            // Auditoría
            $this->auditar('cpu_atencion', 'id', '', $atencion->id, 'INSERCION', "INSERCION DE NUEVA ATENCION CON TRIAJE: {$atencion->id},
                                                                                PACIENTE: {$atencion->id_persona},
                                                                                FUNCIONARIO: {$atencion->id_funcionario},
                                                                                TALLA: {$triaje->talla},
                                                                                PESO: {$triaje->peso},
                                                                                TEMPERATURA: {$triaje->temperatura},
                                                                                SATURACION: {$triaje->saturacion},
                                                                                PRESION SISTOLICA: {$triaje->presion_sistolica},
                                                                                PRESION DIASTOLICA: {$triaje->presion_diastolica},
                                                                                IMC: {$triaje->imc},
                                                                                PESO IDEAL: {$triaje->peso_ideal},
                                                                                ESTADO PACIENTE: {$triaje->estado_paciente}", $request);

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
            'imc' => 'nullable|numeric',
            'peso_ideal' => 'nullable|numeric',
            'estado_paciente' => 'nullable|string|max:50',
            'antecedente_medico' => 'nullable|string',
            'diagnostico' => 'nullable|json',
            'motivo' => 'nullable|string',
            'alergias' => 'nullable|json',
            'recordatorio_24h' => 'nullable|json',
            'analisis_clinicos' => 'nullable|file|mimes:pdf',
            'intolerancias' => 'nullable|json',
            'nombre_caso' => 'nullable|string|max:255',
            'nombre_plan_nutricional' => 'nullable|string|max:255',
            'plan_nutricional' => 'nullable|json',
            'permitidos' => 'nullable|json',
            'no_permitidos' => 'nullable|json',
            'recomendaciones' => 'nullable|string',
            'tipo_atencion' => 'required|string|in:INICIAL,SUBSECUENTE,REAPERTURA',
            'informe_final' => 'nullable|json',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        Log::info('Diagnóstico antes de insertar:', ['diagnostico' => $request->input('diagnostico')]);

        $rutaArchivo = null;

        if ($request->hasFile('analisis_clinicos')) {
            $archivo = $request->file('analisis_clinicos');
            $nombreArchivo = 'analisis_' . $request->input('cedula') . '.pdf';
            $directorioDestino = public_path('Files/analisis_clinico');

            if (!file_exists($directorioDestino)) {
                mkdir($directorioDestino, 0775, true);
            }

            $archivo->move($directorioDestino, $nombreArchivo);
            $rutaArchivo = $nombreArchivo;
            Log::info('Archivo guardado:', ['ruta' => $rutaArchivo]);
        }

        DB::beginTransaction();

        try {
            // Actualizar la derivación
            $derivacion = CpuDerivacion::findOrFail($request->input('id_derivacion'));
            $derivacion->id_estado_derivacion = 2;
            $derivacion->save();

            // Actualizar el estado del turno relacionado
            $turno = CpuTurno::findOrFail($derivacion->id_turno_asignado);
            $turno->estado = 2;
            $turno->save();

            // Guardar caso (si existe id_estado)
            $idCaso = null;

            // Si la atención es INICIAL, se crea un nuevo caso
            if ($request->input('tipo_atencion') === 'INICIAL') {
                if ($request->has('id_estado')) {
                    $caso = new CpuCasosMedicos();
                    $caso->nombre_caso = $request->input('nombre_caso');
                    $caso->id_estado = $request->input('id_estado');
                    $caso->save();
                    $idCaso = $caso->id;
                }
            }
            // Si la atención es SUBSECUENTE, se usa el id_caso del request
            else if ($request->input('tipo_atencion') === 'SUBSECUENTE') {
                $idCaso = $request->input('id_caso'); // Se usa el id_caso enviado en el request

                // Verificar si se envía `informe_final`
                if ($request->has('informe_final')) {
                    $caso = CpuCasosMedicos::findOrFail($idCaso);

                    // Decodificar el nuevo informe enviado
                    $nuevoInforme = json_decode($request->input('informe_final'), true);

                    // Asegurarse de que `informe_final` sea un array antes de agregar la fecha
                    if (!is_array($nuevoInforme)) {
                        return response()->json(['error' => 'Formato inválido en informe_final'], 400);
                    }

                    // Agregar la fecha actual al informe
                    $nuevoInforme['fecha'] = Carbon::now()->toDateTimeString();

                    // Si ya hay algo en `informe_final`, agregar el nuevo informe
                    if (!empty($caso->informe_final)) {
                        $informesPrevios = json_decode($caso->informe_final, true);

                        // Asegurarse de que `informesPrevios` sea un array antes de agregar el nuevo informe
                        if (!is_array($informesPrevios)) {
                            $informesPrevios = [$informesPrevios];
                        }

                        $informesPrevios[] = $nuevoInforme; // Agregar el nuevo informe
                    } else {
                        // Si no hay nada en `informe_final`, simplemente creamos un nuevo array con el nuevo informe
                        $informesPrevios = [$nuevoInforme];
                    }

                    // Actualizar `informe_final` con la nueva información
                    $caso->informe_final = json_encode($informesPrevios);
                    $caso->id_estado = 20; // Actualizar estado del caso
                    $caso->save();
                }
            }
            // Si la atención es REAPERTURAR, se actualiza el estado del caso a 8
            else if ($request->input('tipo_atencion') === 'REAPERTURA') {
                // Asegúrate de obtener el id del caso enviado en la solicitud
                $idCaso = $request->input('id_caso'); // Se usa el id_caso enviado en el request

                if (!$idCaso) {
                    return response()->json(['error' => 'id_caso es requerido para reaperturar un caso.'], 400);
                }

                // Encontrar el caso correspondiente y actualizar su estado
                $caso = CpuCasosMedicos::findOrFail($idCaso);
                $caso->id_estado = 8; // Actualizar el estado del caso a '8' para reapertura
                $caso->save();
            }

            // Guardar la atención
            $atencion = new CpuAtencion();
            $atencion->id_funcionario = $request->input('id_funcionario');
            $atencion->id_persona = $request->input('id_paciente');
            $atencion->via_atencion = $request->input('via_atencion');
            $atencion->motivo_atencion = $request->input('motivo');
            $atencion->id_tipo_usuario = $request->input('id_tipo_usuario');
            $atencion->diagnostico = is_array($request->diagnostico) ? json_encode($request->diagnostico) : $request->diagnostico;
            $atencion->detalle_atencion = 'Atención Nutrición';
            $atencion->fecha_hora_atencion = Carbon::now();
            $atencion->anio_atencion = Carbon::now()->year;
            $atencion->recomendacion = $request->input('recomendaciones');
            $atencion->tipo_atencion = $request->input('tipo_atencion');
            $atencion->id_caso = $idCaso;
            $atencion->save();

            // Extraer ID de la atención
            $idAtencion = $atencion->id;
            Log::info("📌 ID de la atención guardada: " . $idAtencion);

            $triaje = CpuAtencionTriaje::where('id_atencion', $idAtencion)->first();
            $updateData = [
                'talla' => $request->input('talla'),
                'peso' => $request->input('peso'),
                'temperatura' => $request->input('temperatura'),
                'saturacion' => $request->input('saturacion'),
                'presion_sistolica' => $request->input('presion_sistolica'),
                'presion_diastolica' => $request->input('presion_diastolica'),
            ];

            if ($triaje) {
                foreach ($updateData as $key => $value) {
                    if ($triaje->$key != $value) {
                        $triaje->$key = $value;
                    }
                }
                $triaje->save();
            } else {
                $updateData['id_atencion'] = $idAtencion;
                CpuAtencionTriaje::create($updateData);
            }

            // Guardar la atención nutricional
            Log::info('ID_DERIVACION:', ['id_derivacion' => $request->input('id_derivacion')]);
            $nutricion = new CpuAtencionNutricion();
            $nutricion->id_atencion = $idAtencion;
            $nutricion->imc = $request->input('imc');
            $nutricion->peso_ideal = $request->input('peso_ideal');
            $nutricion->estado_paciente = $request->input('estado_paciente');
            // $nutricion->antecedente_medico = $request->input('antecedente_medico');
            $nutricion->recordatorio_24h = json_decode($request->input('recordatorio_24h'), true);
            $nutricion->analisis_clinicos = $rutaArchivo;
            $nutricion->alergias = json_decode($request->input('alergias'), true);
            $nutricion->intolerancias = json_decode($request->input('intolerancias'), true);
            $nutricion->nombre_plan_nutricional = $request->input('nombre_plan_nutricional');
            $nutricion->plan_nutricional = json_decode($request->input('plan_nutricional'), true);
            $nutricion->permitidos = json_decode($request->input('permitidos'), true);
            $nutricion->no_permitidos = json_decode($request->input('no_permitidos'), true);

            if ($rutaArchivo) {
                $nutricion->analisis_clinicos = $rutaArchivo;
            }

            $nutricion->save();

            $nombrePlan = $request->input('nombre_plan_nutricional', 'Plan Nutricional');
            $planNutricional = json_decode($request->input('plan_nutricional'), true) ?? [];
            $permitidos = json_decode($request->input('permitidos'), true) ?? [];
            $noPermitidos = json_decode($request->input('no_permitidos'), true) ?? [];

            // 📌 Construcción del formato de texto estructurado
            $planTexto = "📌 *Plan Nutricional: {$nombrePlan}*\n\n";

            // 🍏 Alimentos Permitidos
            $planTexto .= "🍏 *Alimentos Permitidos:*\n";
            if (!empty($permitidos)) {
                foreach ($permitidos as $alimento) {
                    $planTexto .= "- {$alimento}\n";
                }
            } else {
                $planTexto .= "- No se han especificado alimentos permitidos.\n";
            }

            // 🚫 Alimentos No Permitidos
            $planTexto .= "\n🚫 *Alimentos No Permitidos:*\n";
            if (!empty($noPermitidos)) {
                foreach ($noPermitidos as $alimento) {
                    $planTexto .= "- {$alimento}\n";
                }
            } else {
                $planTexto .= "- No se han especificado alimentos no permitidos.\n";
            }

            // 📅 Plan Nutricional Semanal
            $planTexto .= "\n📅 *Distribución Semanal:*\n";

            $diasSemana = [
                'lunes' => '🔹 *Lunes*',
                'martes' => '🔹 *Martes*',
                'miercoles' => '🔹 *Miércoles*',
                'jueves' => '🔹 *Jueves*',
                'viernes' => '🔹 *Viernes*',
                'sabado' => '🔹 *Sábado*',
                'domingo' => '🔹 *Domingo*'
            ];

            foreach ($diasSemana as $dia => $nombreDia) {
                if (isset($planNutricional[$dia])) {
                    $planTexto .= "\n{$nombreDia}\n";
                    foreach ($planNutricional[$dia] as $comida => $descripcion) {
                        $emoji = match ($comida) {
                            'desayuno' => '🍽️',
                            'almuerzo' => '🍛',
                            'merienda' => '🍵',
                            'entreComida1' => '🍏',
                            'entreComida2' => '🥑',
                            default => '🍴',
                        };
                        $planTexto .= "  - {$emoji} *" . ucfirst($comida) . ":* {$descripcion}\n";
                    }
                    $planTexto .= "-----------------------\n";
                }
            }

            // Verificar si se envía el `id_turno_asignado`
            if ($request->filled('id_turno_asignado')) {
                Log::info('Valor de id_turno_asignado:', ['id_turno_asignado' => $request->input('id_turno_asignado')]);
                try {
                    // Validar los datos de derivación
                    $derivacionData = $request->validate([
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

                    // Lógica adicional si la validación es exitosa
                    $derivacionData['ate_id'] = $atencion->id;
                    $derivacionData['id_funcionario_que_derivo'] = $request->input('id_funcionario');
                    $derivacionData['fecha_derivacion'] = Carbon::now();
                    $derivacion = CpuDerivacion::create($derivacionData);

                    // Actualizar el estado del turno relacionado
                    $turno = CpuTurno::findOrFail($derivacionData['id_turno_asignado']);
                    $turno->estado = 2; // Actualiza el estado del turno a 2
                    $turno->save();

                    // ✅ Enviar correos según la lógica del controlador
                    $correoController = new CpuCorreoEnviadoController();

                    // 📩 Enviar correo de atención al paciente
                    $correoAtencionPaciente = $correoController->enviarCorreoAtencionAreaSaludPaciente(new Request([
                        'id_atencion' => $idAtencion,
                        'id_area_atencion' => $request->input('id_area'),
                        'fecha_hora_atencion' => Carbon::now()->format("Y-m-d H:i:s"),
                        'motivo_atencion' => $request->input('motivo'),
                        'id_paciente' => $request->input('id_paciente'),
                        'id_funcionario' => $request->input('id_funcionario'),
                        'plan_nutricional_texto' => $planTexto,
                    ]));

                    if (!$correoAtencionPaciente->isSuccessful()) {
                        // ❌ Si falla el correo, eliminar la atención guardada
                        $atencion->delete();
                        $nutricion->delete();
                        DB::rollBack();
                        return response()->json(['error' => 'Error al enviar el correo de atención, la atención no fue guardada'], 500);
                    }

                    // 📩 Enviar correos de derivación si aplica
                    if ($request->filled('id_doctor_al_que_derivan')) {
                        $correoDerivacionPaciente = $correoController->enviarCorreoDerivacionAreaSaludPaciente(new Request([
                            'id_atencion' => $idAtencion,
                            'id_area_atencion' => $request->input('id_area'),
                            'motivo_derivacion' => $request->input('motivo_derivacion'),
                            'id_paciente' => $request->input('id_paciente'),
                            'id_funcionario' => $request->input('id_funcionario'),
                            'id_doctor_al_que_derivan' => $request->input('id_doctor_al_que_derivan'),
                            'id_area_derivada' => $request->input('id_area_derivada'),
                            'fecha_para_atencion' => $request->input('fecha_para_atencion'),
                            'hora_para_atencion' => $request->input('hora_para_atencion'),
                        ]));

                        if (!$correoDerivacionPaciente->isSuccessful()) {
                            $atencion->delete();
                            $nutricion->delete();
                            DB::rollBack();
                            return response()->json(['error' => 'Error al enviar el correo de derivación al paciente, la atención no fue guardada'], 500);
                        }

                        $correoDerivacionFuncionario = $correoController->enviarCorreoDerivacionAreaSaludFuncionario(new Request([
                            'id_atencion' => $idAtencion,
                            'id_area_atencion' => $request->input('id_area'),
                            'motivo_derivacion' => $request->input('motivo_derivacion'),
                            'id_paciente' => $request->input('id_paciente'),
                            'id_funcionario' => $request->input('id_funcionario'),
                            'id_doctor_al_que_derivan' => $request->input('id_doctor_al_que_derivan'),
                            'id_area_derivada' => $request->input('id_area_derivada'),
                            'fecha_para_atencion' => $request->input('fecha_para_atencion'),
                            'hora_para_atencion' => $request->input('hora_para_atencion'),
                        ]));

                        if (!$correoDerivacionFuncionario->isSuccessful()) {
                            $atencion->delete();
                            $nutricion->delete();
                            DB::rollBack();
                            return response()->json(['error' => 'Error al enviar el correo de derivación al funcionario, la atención no fue guardada'], 500);
                        }
                    }
                } catch (\Illuminate\Validation\ValidationException $e) {
                    // Capturar los errores de validación y devolver una respuesta JSON
                    return response()->json([
                        'error' => 'Error de validación',
                        'messages' => $e->errors(), // Aquí se devuelven los detalles de los errores
                    ], 422);
                }
            }

            DB::commit();

            // Auditoría
            $this->auditar('cpu_atencion', 'id', '', $atencion->id, 'INSERCION', "INSERCION DE NUEVA ATENCION NUTRICION: {$atencion->id}
                                                                                PACIENTE: {$request->input('id_persona')},
                                                                                FUNCIONARIO: {$request->input('id_funcionario')},
                                                                                MOTIVO DE ATENCION: {$request->input('motivo_atencion')},
                                                                                FECHA Y HORA DE ATENCION: {$request->input('fecha_hora_atencion')},
                                                                                ANIO DE ATENCION: {$request->input('anio_atencion')}", $request);

            return response()->json(['success' => true, 'nutricion_id' => $nutricion->id]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al guardar la atención nutricional:', ['exception' => $e->getMessage()]);
            return response()->json(['error' => 'Error al guardar la atención nutricional'], 500);
        }
    }

    public function guardarAtencionMedicinaGeneral(Request $request)
    {
        // Validar que los campos requeridos estén presentes
        $request->validate([
            'id_funcionario' => 'required|integer|exists:users,id',
            'id_persona' => 'required|integer|exists:cpu_personas,id',
            'via_atencion' => 'required|string',
            'motivo_atencion' => 'required|string',
            'fecha_hora_atencion' => 'required|date_format:Y-m-d H:i:s',
            'anio_atencion' => 'required|integer',
            'detalle_atencion' => 'required|string',
            'id_tipo_usuario' => 'required|integer|exists:cpu_tipo_usuario,id',
            'evolucion_enfermedad' => 'nullable|string',
            'diagnostico' => 'nullable|array|min:0',
            'prescripcion' => 'nullable|string',
            'recomendacion' => 'nullable|string',
            'tipo_atencion' => 'required|string',
            'id_cie10' => 'nullable|integer|exists:cpu_cie10,id',
            'derivado' => 'nullable|boolean',
        ]);

        $derivado = $request->input('derivado');

        DB::beginTransaction();

        try {
            // Guardar en cpu_atenciones
            $atencion = new CpuAtencion();
            $atencion->id_funcionario = $request->input('id_funcionario');
            $atencion->id_persona = $request->input('id_persona');
            $atencion->via_atencion = $request->input('via_atencion');
            $atencion->motivo_atencion = $request->input('motivo_atencion');
            $atencion->fecha_hora_atencion = $request->input('fecha_hora_atencion');
            $atencion->anio_atencion = $request->input('anio_atencion');
            $atencion->detalle_atencion = $request->input('detalle_atencion');
            $atencion->id_caso = $request->input('id_caso');
            $atencion->id_tipo_usuario = $request->input('id_tipo_usuario');
            $atencion->evolucion_enfermedad = $request->input('enfermedad_actual');
            $atencion->diagnostico = !empty($request->diagnostico) ? json_encode($request->diagnostico) : null;
            $atencion->prescripcion = $request->input('planes_tratamiento');
            $atencion->recomendacion = $request->input('recomendacion');
            $atencion->tipo_atencion = $request->input('tipo_atencion');
            $atencion->id_cie10 = $request->input('id_cie10');
            $atencion->tipo_atencion = $request->input('tipo_atencion');
            $atencion->id_estado = 1;
            $atencion->save();

            $id_atencion = $atencion->id;

            // Guardar en cpu_atenciones_medicina_general
            $medicinaGeneral = new CpuAtencionMedicinaGeneral();
            $medicinaGeneral->id_atencion = $atencion->id;
            $medicinaGeneral->organos_sistemas = !empty($request->input('revision_organos'));
            $medicinaGeneral->examen_fisico = !empty($request->input('examen_fisico'));
            $medicinaGeneral->detalle_organos_sistemas = $request->input('detalle_revision_organos');
            $medicinaGeneral->detalle_examen_fisico = $request->input('detalle_examen_fisico');
            $medicinaGeneral->insumos_medicos = $request->input('insumos');
            $medicinaGeneral->medicamentos = $request->input('medicamentos');
            $medicinaGeneral->save();

            // Guardar insumos
            if ($request->has('insumos')) {
                foreach ($request->input('insumos') as $insumo) {
                    $insumoOcupado = new CpuInsumoOcupado();
                    $insumoOcupado->id_insumo = $insumo['id'];
                    $insumoOcupado->id_atencion_medicina_general = $medicinaGeneral->id;
                    $insumoOcupado->id_funcionario = $request->input('id_funcionario');
                    $insumoOcupado->id_paciente = $request->input('id_persona');
                    $insumoOcupado->cantidad_ocupado = $insumo['cantidad'];
                    $insumoOcupado->detalle_ocupado = $request->input('detalle_atencion');
                    $insumoOcupado->fecha_uso = now();
                    $insumoOcupado->save();
                    // Actualizar la cantidad disponible del insumo o medicamento
                    $insumoresul = CpuInsumo::find($insumo['id']);
                    if ($insumoresul) {
                        $insumoresul->decrement('cantidad_unidades', (int)$insumo['cantidad']);
                    }
                }
            }

            // Guardar medicamentos
            if ($request->has('medicamentos')) {
                foreach ($request->input('medicamentos') as $medicamento) {
                    $insumoOcupado = new CpuInsumoOcupado();
                    $insumoOcupado->id_insumo = $medicamento['id'];
                    $insumoOcupado->id_atencion_medicina_general = $medicinaGeneral->id;
                    $insumoOcupado->id_funcionario = $request->input('id_funcionario');
                    $insumoOcupado->id_paciente = $request->input('id_persona');
                    $insumoOcupado->cantidad_ocupado = $medicamento['cantidad'];
                    $insumoOcupado->detalle_ocupado = $request->input('detalle_atencion');
                    $insumoOcupado->fecha_uso = now();
                    $insumoOcupado->save();
                    // Actualizar la cantidad disponible del insumo o medicamento
                    $insumo = CpuInsumo::find($medicamento['id']);
                    if ($insumo) {
                        $insumo->decrement('cantidad_unidades', (int)$medicamento['cantidad']);
                    }
                }
            }

            // Manejar derivación si está marcada
            if ($request->input('derivacion')) {
                $derivacion = new CpuDerivacion();
                $derivacion->ate_id = $atencion->id;
                $derivacion->id_doctor_al_que_derivan = $request->input('derivacion.id_doctor_al_que_derivan');
                $derivacion->id_paciente = $request->input('id_persona');
                $derivacion->motivo_derivacion = $request->input('derivacion.motivo_derivacion');
                $derivacion->id_area = $request->input('derivacion.id_area');
                $derivacion->fecha_para_atencion = $request->input('derivacion.fecha_para_atencion');
                $derivacion->hora_para_atencion = $request->input('derivacion.hora_para_atencion');
                $derivacion->id_estado_derivacion = $request->input('derivacion.id_estado_derivacion');
                $derivacion->id_turno_asignado = $request->input('derivacion.id_turno_asignado');
                $derivacion->id_funcionario_que_derivo = $request->input('id_funcionario'); // Corregido para utilizar Auth::id() en lugar de Auth::user()->id
                $derivacion->fecha_derivacion = now();
                $derivacion->save();

                // Actualizar turno
                $turno = CpuTurno::find($request->input('derivacion.id_turno_asignado'));
                $turno->id_paciente = $request->input('id_persona');
                $turno->estado = 7;
                $turno->save();
            }

            // Actualizar la derivación original
            if ($request->has('id_derivacion')) {
                $derivacionOriginal = CpuDerivacion::find($request->input('id_derivacion'));
                if ($derivacionOriginal) {
                    $derivacionOriginal->id_estado_derivacion = 2;
                    $derivacionOriginal->save();
                }
            }

            DB::commit();

            // Auditoría
            $this->auditar('cpu_atencion', 'id', '', $atencion->id, 'INSERCION', "INSERCION DE NUEVA ATENCION MEDICINA GENERAL: {$atencion->id},
                                                                                PACIENTE: {$request->input('id_persona')},
                                                                                FUNCIONARIO: {$request->input('id_funcionario')},
                                                                                VIA DE ATENCION: {$request->input('via_atencion')},
                                                                                MOTIVO DE ATENCION: {$request->input('motivo_atencion')},
                                                                                FECHA Y HORA DE ATENCION: {$request->input('fecha_hora_atencion')},
                                                                                ANIO DE ATENCION: {$request->input('anio_atencion')}", $request);

            // // Llamar a la función enviarCorreoAtencionPaciente del controlador CpuCorreoEnviadoController
            // $correoController = new CpuCorreoEnviadoController();
            // $correoController->enviarCorreoAtencionPaciente($request->all(), $derivado, $id_atencion);
            // // if ($derivado) {
            //     $correoController->enviarCorreoDerivacionPaciente($request->all());
            // // }

            return response()->json(['success' => true, 'atencion_id' => $atencion->id]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al guardar la atención de medicina general:', ['exception' => $e->getMessage()]);
            return response()->json(['error' => 'Error al guardar la atención de medicina general', 'details' => $e->getMessage()], 500);
        }
    }

    public function historiaClinica($id_paciente)
    {
        $historiaClinica = DB::table('cpu_derivaciones as der')
            ->leftJoin('users as usr', 'usr.id', '=', 'der.id_doctor_al_que_derivan')
            ->leftJoin('cpu_personas as per', 'per.id', '=', 'der.id_paciente')
            ->leftJoin('cpu_atenciones as ate', 'ate.id', '=', 'der.ate_id')
            ->leftJoin('cpu_userrole as rol', 'rol.id_userrole', '=', 'der.id_area')
            ->leftJoin('cpu_atenciones_triaje as tri', 'tri.id_atencion', '=', 'der.id')
            ->select(
                'der.ate_id',
                'der.id_doctor_al_que_derivan',
                'der.id_paciente',
                'der.id_area',
                'der.id_estado_derivacion',
                'usr.name as doctor',
                'per.nombres as paciente',
                'per.cedula as cedula',
                'per.fechanaci as fecha_nacimiento',
                'per.discapacidad as discapacidad',
                'per.sexo as sexo',
                'per.imagen',
                'rol.role as area',
                'ate.motivo_atencion as motivo',
                'ate.via_atencion as medio_atencion',
                'ate.diagnostico as diagnostico',
                'ate.evolucion_enfermedad as evolucion_enfermedad',
                'ate.prescripcion',
                'ate.recomendacion',
                'ate.fecha_hora_atencion',
                'tri.talla',
                'tri.peso',
                'tri.temperatura',
                'tri.presion_sistolica',
                'tri.presion_diastolica',
                'tri.saturacion',
                'tri.imc'
            )
            ->where('der.id_paciente', $id_paciente)
            ->whereIn('der.id_area', [7, 8, 9, 11, 13, 14, 15, 16, 17, 18])
            ->where('der.id_estado_derivacion', 2)
            ->orderBy('ate.fecha_hora_atencion', 'desc')
            ->get();

        // Procesar los resultados para formatear el diagnóstico y la URL de la imagen
        $historiaClinica = $historiaClinica->map(function ($item) {
            // Construir la URL completa de la imagen
            if ($item->imagen) {
                $item->imagen = url('Perfiles/' . $item->imagen);
            }

            // Convertir el diagnóstico en una cadena de texto con el formato "CIE10 y descripción"
            if (is_string($item->diagnostico)) {
                $diagnosticos = json_decode($item->diagnostico, true);
                if (is_array($diagnosticos)) {
                    $formattedDiagnosticos = array_map(function ($diagnostico) {
                        // Convertir las claves a minúsculas para facilitar el acceso
                        $diagnostico = array_change_key_case($diagnostico, CASE_LOWER);

                        return 'CIE10: ' . $diagnostico['cie10'] . ' - ' . $diagnostico['diagnostico'] . ' (' . $diagnostico['tipo'] . ')';
                    }, $diagnosticos);
                    $item->diagnostico = implode(', ', $formattedDiagnosticos);
                } else {
                    $item->diagnostico = 'CIE10: No especificado';
                }
            } elseif (is_null($item->diagnostico)) {
                $item->diagnostico = 'CIE10: No especificado';
            }

            return $item;
        });

        // Auditoría
        $this->auditar('cpu_historia_clinica', 'id_paciente', '', $id_paciente, 'CONSULTA', "CONSULTA DE HISTORIA CLINICA: {$id_paciente}");

        return response()->json($historiaClinica)
            ->header('Access-Control-Allow-Origin', '*');
    }

    // función para guardar las atenciones relacionadas a los trámites
    public function guardarAtencionTramites(Request $request)
    {
        // Validar que los campos requeridos estén presentes
        $request->validate([
            'id_funcionario' => 'required|integer|exists:users,id',
            'id_persona' => 'required|integer|exists:cpu_personas,id',
            'motivo_atencion' => 'required|string',
            'fecha_hora_atencion' => 'required|date_format:Y-m-d H:i:s',
            'anio_atencion' => 'required|integer',
            'detalle_atencion' => 'nullable|string',
            'id_tipo_usuario' => 'required|integer|exists:cpu_tipo_usuario,id',
            'tipo_atencion' => 'nullable|string',
            'id_tramite' => 'nullable|integer|exists:cpu_tramites,id_tramite',
            'id_doctor_al_que_derivan' => 'nullable|integer|exists:users,id',
            'id_area' => 'nullable|integer|exists:cpu_userrole,id_userrole',
            'id_turno_asignado' => 'nullable|integer|exists:cpu_turnos,id_turnos',
        ]);

        DB::beginTransaction();

        try {
            // Guardar en cpu_atenciones
            $atencion = new CpuAtencion();
            $atencion->id_funcionario = $request->input('id_funcionario');
            $atencion->id_persona = $request->input('id_persona');
            $atencion->motivo_atencion = $request->input('motivo_atencion');
            $atencion->fecha_hora_atencion = $request->input('fecha_hora_atencion');
            $atencion->anio_atencion = $request->input('anio_atencion');
            $atencion->detalle_atencion = $request->input('detalle_atencion');
            $atencion->id_tipo_usuario = $request->input('id_tipo_usuario');
            $atencion->tipo_atencion = $request->input('tipo_atencion');
            $atencion->id_estado = 1;

            $atencion->save();

            // Auditoría para la inserción de atención
            $this->auditar('cpu_atencion', 'id', '', $atencion->id, 'INSERCION', "INSERCION DE NUEVA ATENCION: {$atencion->id}
                                                                                SOLICITANTE: {$request->input('id_persona')},
                                                                                FUNCIONARIO: {$request->input('id_funcionario')},
                                                                                MOTIVO DE ATENCION: {$request->input('motivo_atencion')},
                                                                                FECHA Y HORA DE ATENCION: {$request->input('fecha_hora_atencion')},
                                                                                ANIO DE ATENCION: {$request->input('anio_atencion')}", $request);

            // Buscar la fecha y hora para atención en la tabla CpuTurno a través del id_turno_asignado
            $turnoAsignado = CpuTurno::where('id_turnos', $request->input('id_turno_asignado'))->first();
            $fechaParaAtencion = substr($turnoAsignado->fehca_turno, 0, 10);
            $horaParaAtencion = $turnoAsignado->hora;

            // Guardar la derivación
            if ($fechaParaAtencion) {
                $derivacion = new CpuDerivacion();
                $derivacion->ate_id = $atencion->id;
                $derivacion->id_doctor_al_que_derivan = $request->input('id_doctor_al_que_derivan');
                $derivacion->id_paciente = $request->input('id_persona');
                $derivacion->motivo_derivacion = $request->input('motivo_atencion');
                $derivacion->id_area = $request->input('id_area');
                $derivacion->fecha_para_atencion = $fechaParaAtencion;
                $derivacion->hora_para_atencion = $horaParaAtencion;
                $derivacion->id_estado_derivacion = 7;
                $derivacion->id_turno_asignado = $request->input('id_turno_asignado');
                $derivacion->id_funcionario_que_derivo = $request->input('id_funcionario');
                $derivacion->fecha_derivacion = now();
                $derivacion->id_tramite = $request->input('id_tramite');
                $derivacion->save();

                // Auditoría para la inserción de derivación
                $this->auditar('cpu_derivacion', 'ate_id', '', $derivacion->ate_id, 'INSERCION', "INSERCION DE NUEVA DERIVACION: {$derivacion->ate_id}", $request);

                // Actualizar turno
                $turno = CpuTurno::find($request->input('id_turno_asignado'));
                $turno->id_paciente = $request->input('id_persona');
                $turno->estado = 7;
                $turno->save();

                // Auditoría para la actualización de turno
                $this->auditar('cpu_turno', 'id_turnos', '', $turno->id_turnos, 'MODIFICACION', "ACTUALIZACION DE TURNO: {$turno->id_turnos}", $request);
            }

            DB::commit();
            return response()->json(['success' => true, 'atencion_id' => $atencion->id]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al guardar la atención de trámites:', ['exception' => $e->getMessage()]);
            return response()->json(['error' => 'Error al guardar la atención de trámites', 'details' => $e->getMessage()], 500);
        }
    }

    // Función para auditar
    private function auditar($tabla, $campo, $dataOld, $dataNew, $tipo, $descripcion, $request = null)
    {
        $usuario = $request ? $request->user()->name : auth()->user()->name;
        $ip = $request ? $request->ip() : request()->ip();
        $ipv4 = gethostbyname(gethostname());
        $publicIp = file_get_contents('http://ipecho.net/plain');
        $ioConcatenadas = 'IP LOCAL: ' . $ip . '  --IPV4: ' . $ipv4 . '  --IP PUBLICA: ' . $publicIp;
        $nombreequipo = gethostbyaddr($ip);
        $userAgent = $request ? $request->header('User-Agent') : request()->header('User-Agent');
        $tipoEquipo = 'Desconocido';

        if (stripos($userAgent, 'Mobile') !== false) {
            $tipoEquipo = 'Celular';
        } elseif (stripos($userAgent, 'Tablet') !== false) {
            $tipoEquipo = 'Tablet';
        } elseif (stripos($userAgent, 'Laptop') !== false || stripos($userAgent, 'Macintosh') !== false) {
            $tipoEquipo = 'Laptop';
        } elseif (stripos($userAgent, 'Windows') !== false || stripos($userAgent, 'Linux') !== false) {
            $tipoEquipo = 'Computador de Escritorio';
        }
        $nombreUsuarioEquipo = get_current_user() . ' en ' . $tipoEquipo;

        $fecha = now();
        $codigo_auditoria = strtoupper($tabla . '_' . $campo . '_' . $tipo );
        DB::table('cpu_auditoria')->insert([
            'aud_user' => $usuario,
            'aud_tabla' => $tabla,
            'aud_campo' => $campo,
            'aud_dataold' => $dataOld,
            'aud_datanew' => $dataNew,
            'aud_tipo' => $tipo,
            'aud_fecha' => $fecha,
            'aud_ip' => $ioConcatenadas,
            'aud_tipoauditoria' => $this->getTipoAuditoria($tipo),
            'aud_descripcion' => $descripcion,
            'aud_nombreequipo' => $nombreequipo,
            'aud_descrequipo' => $nombreUsuarioEquipo,
            'aud_codigo' => $codigo_auditoria,
            'created_at' => now(),
            'updated_at' => now(),

        ]);
    }

    private function getTipoAuditoria($tipo)
    {
        switch ($tipo) {
            case 'CONSULTA':
                return 1;
            case 'INSERCION':
                return 3;
            case 'MODIFICACION':
                return 2;
            case 'ELIMINACION':
                return 4;
            case 'LOGIN':
                return 5;
            case 'LOGOUT':
                return 6;
            case 'DESACTIVACION':
                return 7;
            default:
                return 0;
        }
    }
}
