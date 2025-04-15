<?php

namespace App\Http\Controllers;

use App\Models\CpuBecado; // Importa el modelo CpuBecado
use App\Models\CpuConsumoBecado;
use App\Models\CpuConsumoFuncionarioComunidad;
use App\Models\CpuFuncionarioComunidad;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class CpuConsumoBecadoController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth:api');
    }


    public function registrarConsumo(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'periodo' => 'required|string',
            'identificacion' => 'required|string',
            'tipo_alimento' => 'required|string',
            'monto_facturado' => 'required|numeric',
            'tipo_usuario' => 'required|string',
            'id_sede' => 'required|integer',
            'id_facultad' => 'required|integer',

        ]);

        if ($request->tipo_usuario === 'Ayuda Económica' || $request->tipo_usuario === 'becado') {
            $consumo = new CpuConsumoBecado();
            $consumo->id_becado = $request->id;
            $consumo->periodo = $request->periodo;
            $consumo->identificacion = $request->identificacion;
            $consumo->tipo_alimento = $request->tipo_alimento;
            $consumo->monto_facturado = $request->monto_facturado;
            $consumo->id_sede = $request->id_sede;
            $consumo->id_facultad = $request->id_facultad;
            $consumo->save();

            // Actualizar el monto_consumido en la tabla cpu_becados
            $becado = CpuBecado::where('id', $request->id)->first();
            $becado->monto_consumido += $request->monto_facturado;
            $becado->save();
            $restante = $becado->monto_otorgado - $becado->monto_consumido;
        } else {
            $consumo = new CpuConsumoFuncionarioComunidad();
            $consumo->id_funcionario_comunidad = $request->id;
            $consumo->periodo = $request->periodo;
            $consumo->identificacion = $request->identificacion;
            $consumo->tipo_alimento = $request->tipo_alimento;
            $consumo->monto_facturado = $request->monto_facturado;
            $consumo->id_sede = $request->id_sede;
            $consumo->id_facultad = $request->id_facultad;
            $consumo->forma_pago = $request->forma_pago;
            $consumo->save();
            $restante = 0;
        }

        // Llamar a la función enviarCorreo con los datos necesarios
        $this->enviarCorreo($request, $restante);
        $this->auditar('cpu_consumo_becado', 'registrarConsumo', '', $consumo, 'INSERCION', 'Consumo de alimentos por ayuda económica - Tasty Uleam, Identificacion: ' . $request->identificacion . ' - Monto: ' . $request->monto_facturado . ' - Tipo de alimento: ' . $request->tipo_alimento . ' - Tipo de usuario: ' . $request->tipo_usuario . ' | Sede: ' . $request->id_sede . ' | Facultad: ' . $request->id_facultad);

        return response()->json(['message' => 'Consumo registrado correctamente', 'code' => 200], 200);
    }

    public function registrosPorFechas($fechaInicio, $fechaFin, Request $request)
    {
        $tipo = $request->query('tipo', 'Todos');

        $fechaInicio = Carbon::parse($fechaInicio);
        $fechaFin = Carbon::parse($fechaFin);

        if ($fechaInicio->isSameDay($fechaFin)) {
            $fechaFin->setTime(23, 59, 59);
        }

        $origen = is_array($tipo) ? $tipo['origen'] : $tipo;

        if ($origen === 'Personal Uleam/Otro') {
            // Buscar en CpuConsumoFuncionarioComunidad
            $registrosFuncionarios = CpuConsumoFuncionarioComunidad::whereBetween('created_at', [$fechaInicio, $fechaFin])->get();
            $registros = $registrosFuncionarios;
        } elseif ($origen === 'Ayuda Económica') {
            // Buscar en CpuConsumoBecado
            $registros = CpuConsumoBecado::whereBetween('created_at', [$fechaInicio, $fechaFin])->get();
        } else {
            // Buscar en ambas tablas
            $registrosBecados = CpuConsumoBecado::whereBetween('created_at', [$fechaInicio, $fechaFin])->get();
            $registrosFuncionarios = CpuConsumoFuncionarioComunidad::whereBetween('created_at', [$fechaInicio, $fechaFin])->get();
            $registros = $registrosBecados->concat($registrosFuncionarios);
        }

        // Agrupar y calcular totales por tipo de alimento
        $total_por_tipo = $registros->groupBy('tipo_alimento')
            ->map(function ($items) {
                return [
                    'total' => $items->count(),
                    'valor_vendido' => $items->sum('monto_facturado'),
                ];
            });

        // Calcular totales globales
        $total_global = [
            'total_registros' => $registros->count(),
            'total_monto' => $registros->sum('monto_facturado'),
        ];
        $this->auditar('cpu_consumo_becado', 'registrosPorFechas', '', '', 'CONSULTA', 'Consulta de consumo de alimentos por ayuda económica - Tasty Uleam');

        return response()->json([
            'fecha_inicio' => $fechaInicio->toDateString(),
            'fecha_fin' => $fechaFin->toDateString(),
            'total_por_tipo' => $total_por_tipo,
            'total_global' => $total_global,
        ]);
    }

    // Buscar solo por fechas
    public function detalleRegistros($fechaInicio, $fechaFin, Request $request)
    {
        $tipo = $request->query('tipo', 'Todos');

        Log::info('Tipo recibido en detalleRegistros:', ['tipo' => $tipo]);

        $fechaInicio = Carbon::parse($fechaInicio);
        $fechaFin = Carbon::parse($fechaFin);

        if ($fechaInicio->isSameDay($fechaFin)) {
            $fechaFin->setTime(23, 59, 59);
        }

        $origen = is_array($tipo) && isset($tipo['origen']) ? $tipo['origen'] : $tipo;
        $valor = is_array($tipo) && isset($tipo['valor']) ? $tipo['valor'] : 'Detallado';
        $tipoPersonal = is_array($valor) && isset($valor['tipoPersonal']) ? $valor['tipoPersonal'] : null;
        $tipoReporte = is_array($valor) && isset($valor['tipoReporte']) ? $valor['tipoReporte'] : 'Detallado';

        Log::info('Origen: ' . $origen);
        Log::info('Valor: ' . json_encode($valor));
        Log::info('Tipo Personal: ' . $tipoPersonal);
        Log::info('Tipo Reporte: ' . $tipoReporte);

        if ($origen === 'Personal Uleam/Otro') {
            // $valor = $tipo['valor'];

            if ($tipoPersonal === 'Todos') {
                // Buscar solo en CpuConsumoFuncionarioComunidad y unir con CpuClientesTasty
                $registrosFuncionarios = CpuConsumoFuncionarioComunidad::whereBetween('cpu_consumo_funcionario_comunidad.created_at', [$fechaInicio, $fechaFin])
                    ->join('cpu_funcionario_comunidad', 'cpu_consumo_funcionario_comunidad.id_funcionario_comunidad', '=', 'cpu_funcionario_comunidad.id')
                    ->select('cpu_consumo_funcionario_comunidad.*', 'cpu_funcionario_comunidad.nombres', 'cpu_funcionario_comunidad.apellidos', 'cpu_funcionario_comunidad.cargo_puesto')
                    ->get();
            } else {
                // Buscar solo en CpuConsumoFuncionarioComunidad donde cargo_puesto coincide y unir con CpuClientesTasty
                $registrosFuncionarios = CpuConsumoFuncionarioComunidad::whereBetween('cpu_consumo_funcionario_comunidad.created_at', [$fechaInicio, $fechaFin])
                    ->join('cpu_funcionario_comunidad', 'cpu_consumo_funcionario_comunidad.id_funcionario_comunidad', '=', 'cpu_funcionario_comunidad.id')
                    ->where('cpu_funcionario_comunidad.cargo_puesto', $valor)
                    ->select('cpu_consumo_funcionario_comunidad.*', 'cpu_funcionario_comunidad.nombres', 'cpu_funcionario_comunidad.apellidos', 'cpu_funcionario_comunidad.cargo_puesto')
                    ->get();
            }

            $registros = $registrosFuncionarios;
        } elseif ($origen === 'Ayuda Económica') {
            // Buscar solo en CpuConsumoBecado y unir con CpuBecado
            $registros = CpuConsumoBecado::whereBetween('cpu_consumo_becado.created_at', [$fechaInicio, $fechaFin])
                ->join('cpu_becados', 'cpu_consumo_becado.id_becado', '=', 'cpu_becados.id')
                ->select('cpu_consumo_becado.*', 'cpu_becados.nombres', 'cpu_becados.apellidos')
                ->get()->map(function ($registro) {
                    $registro->cargo_puesto = 'Ayuda Económica';
                    return $registro;
                });
        } else {
            // Buscar en ambas tablas y unir con CpuClientesTasty y CpuBecado
            $registrosBecados = CpuConsumoBecado::whereBetween('cpu_consumo_becado.created_at', [$fechaInicio, $fechaFin])
                ->join('cpu_becados', 'cpu_consumo_becado.id_becado', '=', 'cpu_becados.id')
                ->select('cpu_consumo_becado.*', 'cpu_becados.nombres', 'cpu_becados.apellidos')
                ->get()->map(function ($registro) {
                    $registro->cargo_puesto = 'Ayuda Económica';
                    return $registro;
                });

            $registrosFuncionarios = CpuConsumoFuncionarioComunidad::whereBetween('cpu_consumo_funcionario_comunidad.created_at', [$fechaInicio, $fechaFin])
                ->join('cpu_funcionario_comunidad', 'cpu_consumo_funcionario_comunidad.id_funcionario_comunidad', '=', 'cpu_funcionario_comunidad.id')
                ->select('cpu_consumo_funcionario_comunidad.*', 'cpu_funcionario_comunidad.nombres', 'cpu_funcionario_comunidad.apellidos', 'cpu_funcionario_comunidad.cargo_puesto')
                ->get();

            $registros = $registrosBecados->concat($registrosFuncionarios);
        }

        // Mapear los detalles de los registros
        if (($origen === 'Ayuda Económica' || $origen === 'Todos' || $origen === 'Personal Uleam/Otro') && ($valor === 'Consolidado' || $tipoReporte === 'Consolidado')) {
            // Aquí, asumimos que quieres agrupar por la identificación del individuo y sumar los montos facturados
            $detalles = $registros->groupBy('identificacion')
                ->map(function ($group) {
                    return [
                        'nombres_completos' => $group->first()->apellidos . ' ' . $group->first()->nombres,
                        'cargo_puesto' => $group->first()->cargo_puesto ?? 'Desconocido',
                        'identificacion' => $group->first()->identificacion,
                        'tipo_alimento' => $group->first()->tipo_alimento,
                        'monto_facturado' => $group->sum('monto_facturado'),  // Suma de montos facturados
                    ];
                })->values();
        } else {
            // Lógica original de mapeo si no se requiere consolidación
            $detalles = $registros->map(function ($registro) {
                return [
                    'nombres_completos' => $registro->apellidos . ' ' . $registro->nombres,
                    'cargo_puesto' => $registro->cargo_puesto ?? 'Desconocido',
                    'identificacion' => $registro->identificacion,
                    'tipo_alimento' => $registro->tipo_alimento,
                    'monto_facturado' => $registro->monto_facturado,
                ];
            });
        }

        // Log de los detalles que se devuelven
        // Log::info('Detalles devueltos en detalleRegistros:', ['detalles' => $detalles]);
        $this->auditar('cpu_consumo_becado', 'detalleRegistros', '', '', 'CONSULTA', 'Consulta de consumo de alimentos por ayuda económica - Tasty Uleam');

        return response()->json([
            'fecha_inicio' => $fechaInicio->toDateString(),
            'fecha_fin' => $fechaFin->toDateString(),
            'detalles' => $detalles,
        ]);
    }


    public function enviarCorreo(Request $request, $restanted)
    {
        try {
            Log::info('Datos recibidos en enviarCorreo', [
                'request_all' => $request->all(),
                'restante' => $restanted
            ]);

            $identificacion = $request->input('identificacion');
            $nombresd = $request->input("nombres");
            $apellidosd = $request->input("apellidos");
            $monto_otorgadod = $request->input('monto_otorgado');
            $tipo_alimentos = json_decode($request->input('tipo_alimento'), true);
            $monto_facturadod = $request->input('monto_facturado');
            $tipo_usuario = $request->input('tipo_usuario');

            // Buscar email por identificación
            $emaile = null;

            $funcionario = CpuFuncionarioComunidad::where('identificacion', $identificacion)->first();
            if ($funcionario && $funcionario->email) {
                $emaile = $funcionario->email;
            } else {
                $becado = CpuBecado::where('identificacion', $identificacion)->first();
                if ($becado && $becado->email) {
                    $emaile = $becado->email;
                }
            }

            if (!$emaile) {
                Log::warning('No se encontró email ni en funcionarios ni en becados', ['identificacion' => $identificacion]);
                return; // No continúa si no hay email
            }

            // Construcción del cuerpo del correo
            $detallesAlimentos = "<ul>";
            foreach ($tipo_alimentos as $alimento) {
                $detallesAlimentos .= "<li>" . htmlspecialchars($alimento['descripcion']) . " - Cantidad: " . $alimento['cantidad'] . ", Precio: $" . number_format($alimento['precio'], 2) . "</li>";
            }
            $detallesAlimentos .= "</ul>";

            if ($tipo_usuario === 'Ayuda Económica') {
                $cuerpoCorreo = "<p>Estimado(a) $apellidosd $nombresd; La EP Uleam, registra el consumo de los siguientes alimentos: $detallesAlimentos Del total de \$$monto_otorgadod dólares, aún tiene disponible \$$restanted dólares. Saludos cordiales.</p>";
            } else {
                $cuerpoCorreo = "<p>Estimado(a) $apellidosd $nombresd; La EP Uleam, registra el consumo de los siguientes alimentos: $detallesAlimentos Saludos cordiales.</p>";
            }

            $persona = [
                "destinatarios" => $emaile,
                "cc" => "",
                "cco" => "",
                "asunto" => "Consumo de alimentos por ayuda económica - Tasty Uleam",
                "cuerpo" => $cuerpoCorreo
            ];

            $datosCodificados = json_encode($persona);
            $url = "https://prod-44.westus.logic.azure.com:443/workflows/4046dc46113a4d8bb5da374ef1ee3e32/triggers/manual/paths/invoke?api-version=2016-06-01&sp=%2Ftriggers%2Fmanual%2Frun&sv=1.0&sig=lA40KwffEyLqEjVA4uyHaWAHblO77vk2jXYEkjUG08s";

            $ch = curl_init($url);
            curl_setopt_array($ch, array(
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => $datosCodificados,
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json',
                    'Content-Length' => strlen($datosCodificados),
                ),
                CURLOPT_RETURNTRANSFER => true,
                // CURLOPT_TIMEOUT_MS => 100,
                // CURLOPT_CONNECTTIMEOUT_MS => 100,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
            ));
            $resultado = curl_exec($ch);
            Log::info('Correo disparado (sin esperar respuesta)', ['email' => $emaile]);
            $codigoRespuesta = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($codigoRespuesta === 200) {
                Log::info('Correo enviado correctamente', ['email' => $emaile]);
            } else {
                Log::warning('Error al enviar correo', [
                    'status' => $codigoRespuesta,
                    'curl_error' => $curlError,
                    'respuesta' => $resultado,
                    'email' => $emaile
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Excepción al enviar correo', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);
        }
    }


    //funcion para auditar
    private function auditar($tabla, $campo, $dataOld, $dataNew, $tipo, $descripcion, $request = null)
    {
        $usuario = $request && !is_string($request) ? $request->user()->name : auth()->user()->name;
        $ip = $request && !is_string($request) ? $request->ip() : request()->ip();
        $ipv4 = gethostbyname(gethostname());
        $publicIp = file_get_contents('http://ipecho.net/plain');
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
