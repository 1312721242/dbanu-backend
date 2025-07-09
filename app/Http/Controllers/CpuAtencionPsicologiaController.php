<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Models\CpuAtencionPsicologia;
use App\Models\CpuAtencion;
use App\Models\CpuCasosMedicos;
use App\Models\CpuDerivacion;
use App\Models\CpuAtencionTriaje;
use App\Models\CpuTurno;
use App\Models\CpuCie10;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB; // Importa DB para transacciones
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
class CpuAtencionPsicologiaController extends Controller
{
    public function store(Request $request)
    {
        // Validación de los campos generales
        $request->validate([
            'funcionarios' => 'required|integer',
            'id_persona' => 'required|integer',
            'tipo_usuario'=> 'required|integer',
            'medio_atencion' => 'required|string',
            'tipo_atencion' => 'required|string',
            'motivo' => 'nullable|string',
            'evolucion' => 'nullable|string',
            'anio_atencion' => 'required|integer',
            'diagnostico' => 'nullable|array',
            'diagnostico.*.cie10' => 'required|string', // Valida que cada diagnóstico tenga un código CIE-10
            'diagnostico.*.diagnostico' => 'required|string', // Valida que cada diagnóstico tenga una descripción
            'diagnostico.*.tipo' => 'required|string|in:PRESUNTIVO,DEFINITIVO', // Valida que el tipo sea válido
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
            'descripcionfinal' => 'nullable|json',
            'reactivos' => 'nullable|string',
        ]);

        // Inicia la transacción
        DB::beginTransaction();

        try {
            // Crear o asociar el caso y la atención psicológica
            if ($request->activarcaso && in_array($request->tipo_atencion, ['INICIO'])) {
                $nuevoCaso = CpuCasosMedicos::create([
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
                    'id_tipo_usuario' => $request->tipo_usuario,
                    'tipo_atencion' => $request->tipo_atencion,
                    'evolucion_enfermedad' => $request->evolucion,
                    'diagnostico' => is_array($request->diagnostico) ? json_encode($request->diagnostico) : $request->diagnostico,
                    'prescripcion' => $request->observacion,
                    'id_cie10' => $request->id_cie10,
                    'id_estado' =>1,
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
                    'id_tipo_usuario' => $request->tipo_usuario,
                    'tipo_atencion' => $request->tipo_atencion,
                    'evolucion_enfermedad' => $request->evolucion,
                    'diagnostico' => is_array($request->diagnostico) ? json_encode($request->diagnostico) : $request->diagnostico,
                    'prescripcion' => $request->observacion,
                    'id_cie10' => $request->id_cie10,
                    'id_estado' =>1,
                ]);

                if ($request->input('altacaso') && $request->input('tipo_atencion') === 'SUBSECUENTE') {
                    // Obtener el caso médico actual para acceder a la columna informe_final
                    $casoActual = CpuCasosMedicos::find($request->id_caso);

                    $nuevoInforme = json_decode($request->input('descripcionfinal'), true);

                    // Asegurarse de que `informe_final` sea un array antes de agregar la fecha
                    if (!is_array($nuevoInforme)) {
                        return response()->json(['error' => 'Formato inválido en informe_final'], 400);
                    }

                    $nuevoInforme['fecha'] = Carbon::now()->toDateTimeString();

                    // Si ya hay algo en `informe_final`, agregar el nuevo informe
                    if (!empty($casoActual->informe_final)) {
                        $informesPrevios = json_decode($casoActual->informe_final, true);

                        // Asegurarse de que `informesPrevios` sea un array antes de agregar el nuevo informe
                        if (!is_array($informesPrevios)) {
                            $informesPrevios = [$informesPrevios];
                        }

                        $informesPrevios[] = $nuevoInforme; // Agregar el nuevo informe
                    } else {
                        // Si no hay nada en `informe_final`, simplemente creamos un nuevo array con el nuevo informe
                        $informesPrevios = [$nuevoInforme];
                    }

                    // Actualizar el campo informe_final con el nuevo array
                    CpuCasosMedicos::where('id', $request->id_caso)->update([
                        'id_estado' => 20,
                        'informe_final' => json_encode($informesPrevios)  // Utilizar JSON_UNESCAPED_UNICODE para evitar el escape de caracteres
                    ]);
                }
                if ($request->tipo_atencion == 'REAPERTURA') {
                    CpuCasosMedicos::where('id', $request->id_caso)->update(['id_estado' => 8]);
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
                'diagnodo' => $request->referido,
                'accionstico' => $request->diagnostico,
                'referies_afirmativas' => $request->acciones_afirmativas,
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
                // 'descripcionfinal' => $request->descripcionfinal,
                'resu_reactivos'=> $request->reactivos,
            ]);

            // Guardar datos de derivación si el switch de derivación está activo
            if ($request->input('derivacionFlag')) {
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

                $derivacionData['ate_id'] = $cpuAtencion->id;
                $derivacionData['id_funcionario_que_derivo'] = Auth::id();
                $derivacionData['fecha_derivacion'] = Carbon::now();
                $derivacion = CpuDerivacion::create($derivacionData);

                // Actualizar el estado del turno si la derivación es exitosa
                CpuTurno::where('id_turnos', $derivacionData['id_turno_asignado'])
                    ->update(['estado' => 18]);

                // Guardar el triaje siempre después de la derivación
                $triaje = new CpuAtencionTriaje();
                $triaje->id_atencion = $cpuAtencion->id; // Usar el ID de la derivación recién creada
                $triaje->talla = $request->input('talla');
                $triaje->peso = $request->input('peso');
                $triaje->temperatura = $request->input('temperatura');
                $triaje->presion_sistolica = $request->input('presion_sistolica');
                $triaje->presion_diastolica = $request->input('presion_diastolica');
                $triaje->save();
            }

            // Auditoría
            $this->auditar('cpu_atencion_psicologia', 'id', '', $atencionPsicologia->id, 'INSERCION', "INSERCION DE NUEVA ATENCION PSICOLOGIA: {$atencionPsicologia->id},
                                                                                PACIENTE: {$request->input('id_persona')},
                                                                                FUNCIONARIO: {$request->input('id_funcionario')},
                                                                                VIA DE ATENCION: {$request->input('via_atencion')},
                                                                                MOTIVO DE ATENCION: {$request->input('motivo_atencion')},
                                                                                FECHA Y HORA DE ATENCION: {$request->input('fecha_hora_atencion')}");

            // Confirmar la transacción
            DB::commit();

            // return response()->json($atencionPsicologia, 201);
            return response()->json(['success' => true, 'atencion_id' => $cpuAtencion->id]);

        } catch (\Exception $e) {
            // Revertir la transacción en caso de error
            DB::rollBack();

            return response()->json([
                'error' => 'Ocurrió un error durante el proceso de guardado',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    // Nueva función para actualizar el estado de derivacion
    public function actulizarderivacionsico(Request $request)
    {
        $id_derivacion = $request->input('id_derivacion');
        CpuDerivacion::where('id', $id_derivacion)->update(['id_estado_derivacion' => 2]);

        // Auditoría
        $this->auditar('cpu_atencion_psicologia', 'id', '', $id_derivacion, 'MODIFICACION', "ACTUALIZACION DE ESTADO DE DERIVACION: {$id_derivacion}");

        return response()->json(['message' => 'Derivación actualizada correctamente']);
    }

    public function index()
    {
        $casos = CpuCasosMedicos::all();

        // Auditoría
        $this->auditar('cpu_atencion_psicologia', 'id', '', '', 'CONSULTA', "CONSULTA DE TODOS LOS CASOS MEDICOS");

        return response()->json($casos);
    }
    public function obtenerCie10(Request $request)
    {
        $query = $request->input('query');

        $cie10 = DB::table('cpu_cie10')
            ->where('cie10', 'ILIKE', "%{$query}%")
            ->orWhere('descripcioncie', 'ILIKE', "%{$query}%")
            ->get();

        // Auditoría
        $this->auditar('cpu_atencion_psicologia', 'id', '', '', 'CONSULTA', "CONSULTA DE TODOS LOS CIE10");

        return response()->json($cie10);
    }

    //funcion para auditar
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
