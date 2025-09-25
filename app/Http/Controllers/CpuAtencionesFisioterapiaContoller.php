<?php

namespace App\Http\Controllers;

use App\Models\CpuAtencion;
use Illuminate\Http\Request;
use App\Models\CpuAtencionFisioterapia;
use App\Models\CpuAtencionTriaje;
use App\Models\CpuCasosMedicos;
use App\Models\CpuDerivacion;
use App\Models\CpuTurno;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class CpuAtencionesFisioterapiaContoller extends Controller
{

    public function guardarAtencionFisioterapia(Request $request)
    {
        // ─────────────────────────────────────────────
        // 1) VALIDACIÓN BASE
        // ─────────────────────────────────────────────
        $validator = Validator::make($request->all(), [
            'id_funcionario'               => 'required|integer',
            'id_paciente'                  => 'required|integer',
            'id_derivacion'                => 'required|integer|exists:cpu_derivaciones,id',
            'id_tipo_usuario'              => 'required|integer', // se usa en la atención
            'numero_comprobante'           => 'nullable|string',
            'valor_cancelado'              => 'nullable|numeric|min:0',
            'total_sesiones'               => 'required_if:tipo_atencion,INICIAL,REAPERTURA|integer|min:1',
            'numero_sesion'                => 'nullable|integer',
            'partes'                       => 'required|string',
            'subpartes'                    => 'required|string',
            'eva'                          => 'required|integer',
            'test_goniometrico'            => 'nullable|json',
            'test_circunferencial'         => 'nullable|json',
            'test_longitudinal'            => 'nullable|json',
            'valoracion_fisioterapeutica'  => 'required|string',
            'diagnostico_fisioterapeutico' => 'required|string',
            'aplicaciones_terapeuticas'    => 'nullable|json',
            'tipo_atencion'                => 'required|string|in:INICIAL,SUBSECUENTE,REAPERTURA',
            'informe_final'                => 'nullable|json',
            // Agendamiento
            // 'turnos'                       => 'exclude_unless:tipo_atencion,REAPERTURA|required|json',
            // 'id_area'                      => 'exclude_unless:tipo_atencion,REAPERTURA|required|integer',
            'turnos'                       => 'nullable|json',
            'id_area'                      => 'nullable|integer',
            'puede_agendar'                => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        // ─────────────────────────────────────────────
        // 1.1) REGLAS ESPECÍFICAS / NORMALIZACIONES
        // ─────────────────────────────────────────────
        $tipo = $request->input('tipo_atencion');

        $puedeAgendar = filter_var($request->input('puede_agendar', false), FILTER_VALIDATE_BOOLEAN);

        // SUBSECUENTE: si **puede_agendar = true**, exigir estructura mínima de turnos e id_area
        // if ($tipo === 'SUBSECUENTE' && $puedeAgendar) {
        //     if (!$request->filled('id_area')) {
        //         return response()->json(['error' => 'En SUBSECUENTE con agendamiento debes enviar id_area.'], 422);
        //     }
        //     $turnosDecod = json_decode($request->input('turnos'), true);
        //     if (empty($turnosDecod) || !is_array($turnosDecod)) {
        //         return response()->json(['error' => 'En SUBSECUENTE con agendamiento debes enviar al menos un turno.'], 422);
        //     }
        //     foreach ($turnosDecod as $i => $t) {
        //         if (empty($t['id_turno']) || empty($t['fecha']) || empty($t['hora'])) {
        //             return response()->json(['error' => "Turno #" . ($i + 1) . " incompleto (id_turno, fecha, hora son requeridos)."], 422);
        //         }
        //     }
        // }

        if ($tipo === 'SUBSECUENTE' && $puedeAgendar) {
            if ($request->filled('turnos')) {
                $turnosDecod = json_decode($request->input('turnos'), true);
                if (!is_array($turnosDecod)) {
                    return response()->json(['error' => 'Formato inválido en turnos (debe ser JSON array).'], 422);
                }

                foreach ($turnosDecod as $i => $t) {
                    if (empty($t['id_turno']) || empty($t['fecha']) || empty($t['hora'])) {
                        return response()->json(['error' => "Turno #" . ($i + 1) . " incompleto (id_turno, fecha, hora son requeridos)."], 422);
                    }
                }
            }
        }

        // Default coherente: numero_sesion = 0 para INICIAL y REAPERTURA si no viene
        if (in_array($tipo, ['INICIAL', 'REAPERTURA', 'SUBSECUENTE']) && !$request->filled('numero_sesion')) {
            $request->merge(['numero_sesion' => 0]);
        }


        // REAPERTURA: turnos obligatorios y con estructura mínima
        if ($tipo === 'REAPERTURA') {
            $turnosDecod = json_decode($request->input('turnos'), true);
            if (empty($turnosDecod) || !is_array($turnosDecod)) {
                return response()->json(['error' => 'En REAPERTURA debes enviar al menos un turno para agendar.'], 422);
            }
            foreach ($turnosDecod as $i => $t) {
                if (empty($t['id_turno']) || empty($t['fecha']) || empty($t['hora'])) {
                    return response()->json(['error' => "Turno #" . ($i + 1) . " incompleto (id_turno, fecha, hora son requeridos)."], 422);
                }
            }
        }

        // INICIAL: si vienen turnos, exigir id_area (regla de negocio)
        if ($tipo === 'INICIAL' && $request->filled('turnos') && !$request->filled('id_area')) {
            return response()->json(['error' => 'En INICIAL, si envías turnos debes enviar id_area.'], 422);
        }

        // REAPERTURA: debe venir id_caso
        if ($tipo === 'REAPERTURA' && !$request->filled('id_caso')) {
            return response()->json(['error' => 'id_caso es requerido para reaperturar un caso.'], 400);
        }

        DB::beginTransaction();

        try {
            // ─────────────────────────────────────────
            // 2) MARCAR DERIVACIÓN COMO ATENDIDA
            // ─────────────────────────────────────────
            $derivacion = CpuDerivacion::findOrFail($request->input('id_derivacion'));
            $derivacion->id_estado_derivacion = 2;
            $derivacion->save();

            // ─────────────────────────────────────────
            // 3) ACTUALIZAR TURNO ORIGINAL (SI EXISTE)
            // ─────────────────────────────────────────
            if (!empty($derivacion->id_turno_asignado)) {
                if ($turno = CpuTurno::find($derivacion->id_turno_asignado)) {
                    $turno->estado = 2; // atendido/cerrado (ajusta a tu catálogo)
                    $turno->save();
                }
            }

            // ─────────────────────────────────────────
            // 4) CASO / CABECERA
            // ─────────────────────────────────────────
            $idCaso = null;
            if ($tipo === 'INICIAL') {
                if ($request->has('id_estado')) {
                    $caso = new CpuCasosMedicos();
                    $caso->nombre_caso = $request->input('nombre_caso');
                    $caso->id_estado   = $request->input('id_estado');
                    $caso->save();
                    $idCaso = $caso->id;
                }
            } elseif ($tipo === 'SUBSECUENTE') {
                $idCaso = $request->input('id_caso');
                if ($request->has('informe_final')) {
                    $caso = CpuCasosMedicos::findOrFail($idCaso);
                    $nuevoInforme = json_decode($request->input('informe_final'), true);
                    if (!is_array($nuevoInforme)) {
                        return response()->json(['error' => 'Formato inválido en informe_final'], 400);
                    }
                    $nuevoInforme['fecha'] = Carbon::now()->toDateTimeString();
                    if (!empty($caso->informe_final)) {
                        $informesPrevios = json_decode($caso->informe_final, true);
                        if (!is_array($informesPrevios)) $informesPrevios = [$informesPrevios];
                        $informesPrevios[] = $nuevoInforme;
                    } else {
                        $informesPrevios = [$nuevoInforme];
                    }
                    $caso->informe_final = json_encode($informesPrevios);
                    $caso->id_estado = 20; // cerrado con informe (ajusta a tu catálogo)
                    $caso->save();
                }
            } elseif ($tipo === 'REAPERTURA') {
                $idCaso = $request->input('id_caso');
                $caso = CpuCasosMedicos::findOrFail($idCaso);
                $caso->id_estado = 8; // reaperturado (ajusta a tu catálogo)
                $caso->save();
            }

            // ─────────────────────────────────────────
            // 5) ATENCIÓN
            // ─────────────────────────────────────────
            $atencion = new CpuAtencion();
            $atencion->id_funcionario       = $request->input('id_funcionario');
            $atencion->id_persona           = $request->input('id_paciente');
            $atencion->via_atencion         = $request->input('via_atencion');
            $atencion->motivo_atencion      = $request->input('motivo'); // p.ej. "REAPERTURA: <motivo>"
            $atencion->id_tipo_usuario      = $request->input('id_tipo_usuario');
            $atencion->diagnostico          = is_array($request->diagnostico) ? json_encode($request->diagnostico) : $request->diagnostico;
            $atencion->detalle_atencion     = 'ATENCIÓN FISIOTERAPIA';
            $atencion->fecha_hora_atencion  = Carbon::now();
            $atencion->anio_atencion        = Carbon::now()->year;
            $atencion->tipo_atencion        = $tipo;
            $atencion->id_caso              = $idCaso;
            $atencion->save();

            $idAtencion = $atencion->id;
            Log::info("📌 ID de la atención guardada: " . $idAtencion);

            // ─────────────────────────────────────────
            // 6) TRIAJE (UPSERT)
            // ─────────────────────────────────────────
            $triaje = CpuAtencionTriaje::where('id_atencion', $idAtencion)->first();
            $updateData = [
                'talla'              => $request->input('talla'),
                'peso'               => $request->input('peso'),
                'temperatura'        => $request->input('temperatura'),
                'saturacion'         => $request->input('saturacion'),
                'presion_sistolica'  => $request->input('presion_sistolica'),
                'presion_diastolica' => $request->input('presion_diastolica'),
            ];

            if ($triaje) {
                foreach ($updateData as $key => $value) {
                    if ($triaje->$key != $value) $triaje->$key = $value;
                }
                $triaje->save();
            } else {
                $updateData['id_atencion'] = $idAtencion;
                CpuAtencionTriaje::create($updateData);
            }

            // ─────────────────────────────────────────
            // 7) DETALLE FISIOTERAPIA
            // ─────────────────────────────────────────
            $fisioterapia = new CpuAtencionFisioterapia();
            $fisioterapia->id_atencion                  = $idAtencion;
            $fisioterapia->partes                       = $request->input('partes');
            $fisioterapia->subpartes                    = $request->input('subpartes');
            $fisioterapia->eva                          = $request->input('eva');
            $fisioterapia->test_goniometrico            = $request->filled('test_goniometrico')    ? json_decode($request->input('test_goniometrico'), true)    : null;
            $fisioterapia->test_circunferencial         = $request->filled('test_circunferencial') ? json_decode($request->input('test_circunferencial'), true) : null;
            $fisioterapia->test_longitudinal            = $request->filled('test_longitudinal')    ? json_decode($request->input('test_longitudinal'), true)    : null;
            $fisioterapia->valoracion_fisioterapeutica  = $request->input('valoracion_fisioterapeutica');
            $fisioterapia->diagnostico_fisioterapeutico = $request->input('diagnostico_fisioterapeutico');
            $fisioterapia->aplicaciones_terapeuticas    = $request->filled('aplicaciones_terapeuticas') ? json_decode($request->input('aplicaciones_terapeuticas'), true) : null;
            $fisioterapia->numero_comprobante           = $request->input('numero_comprobante');
            $fisioterapia->valor_cancelado              = $request->input('valor_cancelado');
            $fisioterapia->total_sesiones               = $request->input('total_sesiones');
            $fisioterapia->numero_sesion                = $request->input('numero_sesion'); // aquí ya va 0 si no vino
            $fisioterapia->save();

            // ─────────────────────────────────────────
            // 8) CORREOS + DERIVACIONES
            // ─────────────────────────────────────────
            $idsDerivacionesCreadas = [];
            $correoController = new CpuCorreoEnviadoController();

            // Correo de atención a paciente (siempre)
            $correoAtencionPaciente = $correoController->enviarCorreoAtencionAreaSaludPaciente(new Request([
                'id_atencion'         => $idAtencion,
                'id_area_atencion'    => $request->input('id_area'), // puede ser null en INICIAL sin agendamiento
                'fecha_hora_atencion' => Carbon::now()->format("Y-m-d H:i:s"),
                'motivo_atencion'     => $request->input('motivo'),
                'id_paciente'         => $request->input('id_paciente'),
                'id_funcionario'      => $request->input('id_funcionario'),
            ]));
            if (!$correoAtencionPaciente->isSuccessful()) {
                DB::rollBack();
                return response()->json(['error' => 'Error al enviar correo de atención (paciente)'], 500);
            }

            // Derivaciones / agendamientos (INICIAL opcional, REAPERTURA obligatorio ya validado)
            // Derivaciones / agendamientos
            if (
                $tipo === 'INICIAL'
                || $tipo === 'REAPERTURA'
                || ($tipo === 'SUBSECUENTE' && $puedeAgendar)
            ) {
                $turnosJson = $request->input('turnos');

                if (!empty($turnosJson)) {
                    $turnosDecod = json_decode($turnosJson, true);

                    foreach ($turnosDecod as $t) {
                        $horaNorm = (isset($t['hora']) && strlen($t['hora']) === 5) ? $t['hora'] . ':00' : ($t['hora'] ?? null);

                        $derivacionData = [
                            'id_doctor_al_que_derivan'  => $request->input('id_doctor_al_que_derivan'),
                            'id_paciente'               => $request->input('id_paciente'),
                            'motivo_derivacion'         => $request->input('motivo_derivacion'),
                            'detalle_derivacion'        => $request->input('detalle_derivacion'),
                            'id_area'                   => $request->input('id_area'),
                            'fecha_para_atencion'       => $t['fecha'] ?? null,
                            'hora_para_atencion'        => $horaNorm,
                            'id_estado_derivacion'      => $request->input('id_estado_derivacion', 7),
                            'id_turno_asignado'         => $t['id_turno'] ?? null,
                            'ate_id'                    => $idAtencion,
                            'id_funcionario_que_derivo' => $request->input('id_funcionario'),
                            'fecha_derivacion'          => $t['fecha'] ?? null,
                        ];

                        $derivNueva = CpuDerivacion::create($derivacionData);
                        $idsDerivacionesCreadas[] = $derivNueva->id;

                        // marcar turno como asignado
                        if (!empty($t['id_turno'])) {
                            $turnoX = CpuTurno::findOrFail($t['id_turno']);
                            $turnoX->estado = 7; // asignado
                            $turnoX->save();
                        }

                        // correos de derivación (si hay médico destino)
                        if ($request->filled('id_doctor_al_que_derivan')) {
                            $correoDerivacionPaciente = $correoController->enviarCorreoDerivacionAreaSaludPaciente(new Request([
                                'id_atencion'              => $idAtencion,
                                'id_area_atencion'         => $request->input('id_area'),
                                'motivo_derivacion'        => $request->input('motivo_derivacion'),
                                'id_paciente'              => $request->input('id_paciente'),
                                'id_funcionario'           => $request->input('id_funcionario'),
                                'id_doctor_al_que_derivan' => $request->input('id_doctor_al_que_derivan'),
                                'id_area_derivada'         => $request->input('id_area_derivada'),
                                'fecha_para_atencion'      => $t['fecha'] ?? null,
                                'hora_para_atencion'       => $horaNorm,
                            ]));
                            if (!$correoDerivacionPaciente->isSuccessful()) {
                                DB::rollBack();
                                return response()->json(['error' => 'Error correo derivación (paciente)'], 500);
                            }

                            $correoDerivacionFuncionario = $correoController->enviarCorreoDerivacionAreaSaludFuncionario(new Request([
                                'id_atencion'              => $idAtencion,
                                'id_area_atencion'         => $request->input('id_area'),
                                'motivo_derivacion'        => $request->input('motivo_derivacion'),
                                'id_paciente'              => $request->input('id_paciente'),
                                'id_funcionario'           => $request->input('id_funcionario'),
                                'id_doctor_al_que_derivan' => $request->input('id_doctor_al_que_derivan'),
                                'id_area_derivada'         => $request->input('id_area_derivada'),
                                'fecha_para_atencion'      => $t['fecha'] ?? null,
                                'hora_para_atencion'       => $horaNorm,
                            ]));
                            if (!$correoDerivacionFuncionario->isSuccessful()) {
                                DB::rollBack();
                                return response()->json(['error' => 'Error correo derivación (funcionario)'], 500);
                            }
                        }
                    }
                } else if ($tipo === 'REAPERTURA') {
                    // Refuerzo por si llegara a pasar validación
                    return response()->json(['error' => 'En REAPERTURA es obligatorio enviar turnos para agendar.'], 422);
                }
            }

            // ─────────────────────────────────────────
            // 9) AUDITORÍA
            // ─────────────────────────────────────────
            $this->auditar(
                'cpu_atenciones_fisioterapia',
                'id',
                '',
                $fisioterapia->id,
                'INSERCION',
                "INSERCION DE NUEVA ATENCION FISIOTERAPIA: {$fisioterapia->id},
         PACIENTE: {$request->input('id_paciente')},
         FUNCIONARIO: {$request->input('id_funcionario')},
         DERIVACION: {$request->input('id_derivacion')},
         FECHA Y HORA DE ATENCION: {$request->input('fecha_hora_atencion')},
         TIPO DE ATENCION: {$tipo}"
            );

            DB::commit();

            return response()->json([
                'success'              => true,
                'fisioterapia_id'      => $fisioterapia->id,
                'derivaciones_creadas' => $idsDerivacionesCreadas,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al guardar la atención fisioterapia:', ['exception' => $e->getMessage()]);
            return response()->json(['error' => 'Error al guardar la atención fisioterapia'], 500);
        }
    }

    public function obtenerUltimaConsultaFisioterapia($area_atencion, $usr_tipo, $id_persona, $id_caso)
    {
        try {
            // Registrar en logs el área de atención
            Log::info('Área de atención: ' . $area_atencion);

            // Buscar la última atención del paciente
            $ultimaConsulta = CpuAtencion::where('id_persona', $id_persona)
                ->where('id_funcionario', $usr_tipo)
                ->where('id_caso', $id_caso)
                ->orderBy('fecha_hora_atencion', 'desc')
                ->first();

            if (!$ultimaConsulta) {
                return response()->json(['mensaje' => 'No se encontraron consultas para el paciente con el caso especificado'], 204);
            }

            // Formatear la fecha
            $ultimaConsulta->fecha_hora_atencion = Carbon::parse($ultimaConsulta->fecha_hora_atencion)->translatedFormat('l, d F Y');

            // Incluir el diagnóstico
            $ultimaConsulta->diagnostico = $ultimaConsulta->diagnostico ?? 'Sin diagnóstico';

            // Obtener el id_derivacion
            Log::info('ID de la última consulta: ' . $ultimaConsulta->id);
            $atencionFisioterapia = CpuAtencionFisioterapia::where('id_atencion', $ultimaConsulta->id)->first();

            // Convertir a array
            $respuesta = $ultimaConsulta->toArray();

            // Si el área de atención es fisioterapia, traer los datos adicionales
            if (strtoupper($area_atencion) === "FISIOTERAPIA") {
                Log::info("🔍 Buscando datos de fisioterapia en `cpu_atenciones_fisioterapia` con ID_ATENCION: " . $ultimaConsulta->id);

                $atencionFisioterapia = CpuAtencionFisioterapia::where('id_atencion', $ultimaConsulta->id)->first();

                if ($atencionFisioterapia) {
                    Log::info("✅ Datos de fisioterapia encontrados.", $atencionFisioterapia->toArray());

                    // No usar json_decode() porque Laravel ya maneja JSONB como arrays
                    $respuesta['datos_fisioterapia'] = [
                        'id' => $atencionFisioterapia->id,
                        'id_atencion' => $atencionFisioterapia->id_atencion,
                        'numero_comprobante' => $atencionFisioterapia->numero_comprobante,
                        'valor_cancelado' => $atencionFisioterapia->valor_cancelado,
                        'total_sesiones' => $atencionFisioterapia->total_sesiones,
                        'numero_sesion' => $atencionFisioterapia->numero_sesion + 1,
                        'partes' => $atencionFisioterapia->partes ?? '',
                        'subpartes' => $atencionFisioterapia->subpartes ?? '',
                        'eva' => $atencionFisioterapia->eva ?? 0,
                        'test_goniometrico' => is_string($atencionFisioterapia->test_goniometrico)
                            ? json_decode($atencionFisioterapia->test_goniometrico, true)
                            : ($atencionFisioterapia->test_goniometrico ?? []),
                        'test_circunferencial' => is_string($atencionFisioterapia->test_circunferencial)
                            ? json_decode($atencionFisioterapia->test_circunferencial, true)
                            : ($atencionFisioterapia->test_circunferencial ?? []),
                        'test_longitudinal' => is_string($atencionFisioterapia->test_longitudinal)
                            ? json_decode($atencionFisioterapia->test_longitudinal, true)
                            : ($atencionFisioterapia->test_longitudinal ?? []),
                        'valoracion_fisioterapeutica' => $atencionFisioterapia->valoracion_fisioterapeutica ?? '',
                        'diagnostico_fisioterapeutico' => $atencionFisioterapia->diagnostico_fisioterapeutico ?? '',
                        'aplicaciones_terapeuticas' => is_string($atencionFisioterapia->aplicaciones_terapeuticas)
                            ? json_decode($atencionFisioterapia->aplicaciones_terapeuticas, true)
                            : ($atencionFisioterapia->aplicaciones_terapeuticas ?? []),
                        'created_at' => $atencionFisioterapia->created_at,
                        'updated_at' => $atencionFisioterapia->updated_at
                    ];

                    // Auditoría
                    $this->auditar('cpu_atenciones_fisioterapia', 'id', '', $atencionFisioterapia->id, 'CONSULTA', "CONSULTA DE ATENCION FISIOTERAPIA: {$atencionFisioterapia->id}");
                } else {
                    Log::warning("⚠️ No se encontraron datos de fisioterapia.");
                    $respuesta['datos_fisioterapia'] = null;
                }
            }

            return response()->json($respuesta, 200);
        } catch (\Exception $e) {
            Log::error('Error al obtener la última consulta de fisioterapia: ' . $e->getMessage());
            return response()->json(['error' => 'Error al obtener la última consulta de fisioterapia: ' . $e->getMessage()], 500);
        }
    }

    // Función para auditar
    private function auditar($tabla, $campo, $dataOld, $dataNew, $tipo, $descripcion, $request = null)
    {
        $usuario = $request && !is_string($request) ? $request->user()->name : auth()->user()->name;
        $ip = $request && !is_string($request) ? $request->ip() : request()->ip();
        $ipv4 = gethostbyname(gethostname());
        $publicIp = file_get_contents('https://ifconfig.me/ip');
        $ioConcatenadas = 'IP LOCAL: ' . $ip . '  --IPV4: ' . $ipv4 . '  --IP PUBLICA: ' . $publicIp;
        $nombreequipo = gethostbyaddr($ip);
        $userAgent = $request && !is_string($request) ? $request->header('User-Agent') : request()->header('User-Agent');
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
        $codigo_auditoria = strtoupper($tabla . '_' . $campo . '_' . $tipo);
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
