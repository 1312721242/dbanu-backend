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
use Illuminate\Support\Facades\Http;

class CpuConsumoBecadoController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth:api');
    }


    // public function registrarConsumo(Request $request)
    // {
    //     $request->validate([
    //         'id' => 'required|integer',
    //         'periodo' => 'nullable|string',
    //         'identificacion' => 'required|string',
    //         'tipo_alimento' => 'required|string',
    //         'monto_facturado' => 'required|numeric',
    //         'tipo_usuario' => 'required|string',
    //         'id_sede' => 'required|integer',
    //         'id_facultad' => 'required|integer',
    //         'id_user' => 'required|integer',
    //     ]);

    //     if ($request->tipo_usuario === 'Ayuda Económica' || $request->tipo_usuario === 'becado') {
    //         $consumo = new CpuConsumoBecado();
    //         $consumo->id_becado = $request->id;
    //         $consumo->periodo = $request->periodo;
    //         $consumo->identificacion = $request->identificacion;
    //         $consumo->tipo_alimento = $request->tipo_alimento;
    //         $consumo->monto_facturado = $request->monto_facturado;
    //         $consumo->id_sede = $request->id_sede;
    //         $consumo->id_facultad = $request->id_facultad;
    //         $consumo->id_user = $request->id_user;
    //         $consumo->save();

    //         // Actualizar el monto_consumido en la tabla cpu_becados
    //         $becado = CpuBecado::where('id', $request->id)->first();
    //         $becado->monto_consumido += $request->monto_facturado;
    //         $becado->save();
    //         $restante = $becado->monto_otorgado - $becado->monto_consumido;
    //     } else {
    //         $consumo = new CpuConsumoFuncionarioComunidad();
    //         $consumo->id_funcionario_comunidad = $request->id;
    //         // $consumo->periodo = $request->periodo;
    //         $consumo->identificacion = $request->identificacion;
    //         $consumo->tipo_alimento = $request->tipo_alimento;
    //         $consumo->monto_facturado = $request->monto_facturado;
    //         $consumo->id_sede = $request->id_sede;
    //         $consumo->id_facultad = $request->id_facultad;
    //         $consumo->forma_pago = $request->forma_pago;
    //         $consumo->id_user = $request->id_user;
    //         $consumo->save();
    //         $restante = 0;
    //     }

    //     // Llamar a la función enviarCorreo con los datos necesarios
    //     $this->enviarCorreo($request, $restante);
    //     $this->auditar('cpu_consumo_becado', 'registrarConsumo', '', $consumo, 'INSERCION', 'Consumo de alimentos por ayuda económica - Tasty Uleam, Identificacion: ' . $request->identificacion . ' - Monto: ' . $request->monto_facturado . ' - Tipo de alimento: ' . $request->tipo_alimento . ' - Tipo de usuario: ' . $request->tipo_usuario . ' | Sede: ' . $request->id_sede . ' | Facultad: ' . $request->id_facultad);

    //     return response()->json(['message' => 'Consumo registrado correctamente', 'code' => 200], 200);
    // }

    public function registrarConsumo(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'periodo' => 'nullable|string',
            'identificacion' => 'required|string',
            'tipo_alimento' => 'required|string',
            'monto_facturado' => 'required|numeric',
            'tipo_usuario' => 'required|string',
            'id_sede' => 'required|integer',
            'id_facultad' => 'required|integer',
            'id_user' => 'required|integer',
        ]);

        DB::beginTransaction();

        try {
            if ($request->tipo_usuario === 'Ayuda Económica' || $request->tipo_usuario === 'becado') {
                $consumo = new CpuConsumoBecado();
                $consumo->id_becado = $request->id;
                $consumo->periodo = $request->periodo;
                $consumo->identificacion = $request->identificacion;
                $consumo->tipo_alimento = $request->tipo_alimento;
                $consumo->monto_facturado = $request->monto_facturado;
                $consumo->id_sede = $request->id_sede;
                $consumo->id_facultad = $request->id_facultad;
                $consumo->id_user = $request->id_user;
                $consumo->save();

                $becado = CpuBecado::where('id', $request->id)->first();
                $becado->monto_consumido += $request->monto_facturado;
                $becado->save();

                $restante = $becado->monto_otorgado - $becado->monto_consumido;
            } else {
                $consumo = new CpuConsumoFuncionarioComunidad();
                $consumo->id_funcionario_comunidad = $request->id;
                $consumo->identificacion = $request->identificacion;
                $consumo->tipo_alimento = $request->tipo_alimento;
                $consumo->monto_facturado = $request->monto_facturado;
                $consumo->id_sede = $request->id_sede;
                $consumo->id_facultad = $request->id_facultad;
                $consumo->forma_pago = $request->forma_pago;
                $consumo->id_user = $request->id_user;
                $consumo->save();

                $restante = 0;
            }

            //Enviar correo
            $this->enviarCorreo($request, $restante);

            //Auditar
            $this->auditar(
                'cpu_consumo_becado',
                'registrarConsumo',
                '',
                $consumo,
                'INSERCION',
                'Consumo de alimentos por ayuda económica - Tasty Uleam, Identificacion: ' . $request->identificacion .
                    ' - Monto: ' . $request->monto_facturado .
                    ' - Tipo de alimento: ' . $request->tipo_alimento .
                    ' - Tipo de usuario: ' . $request->tipo_usuario .
                    ' | Sede: ' . $request->id_sede .
                    ' | Facultad: ' . $request->id_facultad
            );

            DB::commit(); //Todo correcto

            return response()->json(['message' => 'Consumo registrado correctamente', 'code' => 200], 200);
        } catch (\Exception $e) {
            DB::rollBack(); //Algo falló, revierte todo

            Log::error('Error en registrarConsumo: ' . $e->getMessage(), [
                'request' => $request->all(),
                'exception' => $e
            ]);

            return response()->json(['message' => 'Error al registrar consumo. No se guardaron los cambios.', 'error' => $e->getMessage()], 500);
        }
    }


    public function registrosPorFechas($fechaInicio, $fechaFin, Request $request)
    {
        try {
            $usr_tipo = $request->query('usr_tipo');
            $usr_id = $request->query('usr_id');

            $fechaInicio = Carbon::parse($fechaInicio)->startOfDay();
            $fechaFin = Carbon::parse($fechaFin)->endOfDay();

            if ($fechaInicio->isSameDay($fechaFin)) {
                $fechaFin->setTime(23, 59, 59);
            }

            $becadosQuery = CpuConsumoBecado::whereBetween('created_at', [$fechaInicio, $fechaFin]);
            $funcionariosQuery = CpuConsumoFuncionarioComunidad::whereBetween('created_at', [$fechaInicio, $fechaFin]);

            if (!in_array($usr_tipo, [1, 27])) {
                $becadosQuery->where('id_user', $usr_id);
                $funcionariosQuery->where('id_user', $usr_id);
            }

            $registrosBecados = $becadosQuery->get();
            $registrosFuncionarios = $funcionariosQuery->get();
            $registros = $registrosBecados->concat($registrosFuncionarios);

            $resumen_por_forma_pago = $registros->groupBy(function ($item) {
                return $item instanceof CpuConsumoBecado ? 'Ayuda Económica' : $item->forma_pago;
            })->map(function ($items, $forma) {
                return [
                    'forma_pago' => $forma,
                    'total' => round($items->sum('monto_facturado'), 2),
                    'cantidad' => $items->count()
                ];
            })->values();

            $total_global = [
                'total_registros' => $registros->count(),
                'total_monto' => round($registros->sum('monto_facturado'), 2)
            ];

            $this->auditar('cpu_consumo_becado', 'registrosPorFechas', '', '', 'CONSULTA', "Consulta de consumo de alimentos entre fechas: $fechaInicio a $fechaFin");

            return response()->json([
                'fecha_inicio' => $fechaInicio->toDateString(),
                'fecha_fin' => $fechaFin->toDateString(),
                'total_por_forma_pago' => $resumen_por_forma_pago,
                'total_global' => $total_global,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al procesar la solicitud'], 500);
        }
    }

    // Buscar solo por fechas
    public function detalleRegistros($fechaInicio, $fechaFin, Request $request)
    {
        $usr_tipo = $request->query('usr_tipo');
        $usr_id = $request->query('usr_id');

        $fechaInicio = Carbon::parse($fechaInicio)->startOfDay();
        $fechaFin = Carbon::parse($fechaFin)->endOfDay();

        if ($fechaInicio->isSameDay($fechaFin)) {
            $fechaFin->setTime(23, 59, 59);
        }

        $becadosQuery = CpuConsumoBecado::whereBetween('cpu_consumo_becado.created_at', [$fechaInicio, $fechaFin])
            ->join('cpu_becados', 'cpu_consumo_becado.id_becado', '=', 'cpu_becados.id')
            ->leftJoin('users', 'cpu_consumo_becado.id_user', '=', 'users.id')
            ->leftJoin('cpu_sede', 'cpu_consumo_becado.id_sede', '=', 'cpu_sede.id')
            ->leftJoin('cpu_facultad', 'cpu_consumo_becado.id_facultad', '=', 'cpu_facultad.id')
            ->select(
                'cpu_consumo_becado.*',
                'cpu_becados.nombres',
                'cpu_becados.apellidos',
                'users.name as nombre_usuario',
                'cpu_sede.nombre_sede',
                'cpu_facultad.fac_nombre as nombre_facultad'
            )
            ->orderBy('cpu_consumo_becado.created_at', 'desc');

        $funcionariosQuery = CpuConsumoFuncionarioComunidad::whereBetween('cpu_consumo_funcionario_comunidad.created_at', [$fechaInicio, $fechaFin])
            ->join('cpu_funcionario_comunidad', 'cpu_consumo_funcionario_comunidad.id_funcionario_comunidad', '=', 'cpu_funcionario_comunidad.id')
            ->leftJoin('users', 'cpu_consumo_funcionario_comunidad.id_user', '=', 'users.id')
            ->leftJoin('cpu_sede', 'cpu_consumo_funcionario_comunidad.id_sede', '=', 'cpu_sede.id')
            ->leftJoin('cpu_facultad', 'cpu_consumo_funcionario_comunidad.id_facultad', '=', 'cpu_facultad.id')
            ->select(
                'cpu_consumo_funcionario_comunidad.*',
                'cpu_funcionario_comunidad.nombres',
                'cpu_funcionario_comunidad.apellidos',
                'cpu_funcionario_comunidad.cargo_puesto',
                'users.name as nombre_usuario',
                'cpu_sede.nombre_sede',
                'cpu_facultad.fac_nombre as nombre_facultad'
            )
            ->orderBy('cpu_consumo_funcionario_comunidad.created_at', 'desc');

        if (!in_array($usr_tipo, [1, 27])) {
            $becadosQuery->where('cpu_consumo_becado.id_user', $usr_id);
            $funcionariosQuery->where('cpu_consumo_funcionario_comunidad.id_user', $usr_id);
        }

        $registrosBecados = $becadosQuery->get()->map(function ($registro) {
            $registro->cargo_puesto = 'Ayuda Económica';
            return $registro;
        });

        $registrosFuncionarios = $funcionariosQuery->get();

        $registros = $registrosBecados->concat($registrosFuncionarios);

        $detalles = $registros->map(function ($registro) {
            return [
                'nombres_completos' => $registro->apellidos . ' ' . $registro->nombres,
                'cargo_puesto' => $registro->cargo_puesto ?? 'Desconocido',
                'identificacion' => $registro->identificacion,
                'tipo_alimento' => $registro->tipo_alimento,
                'monto_facturado' => $registro->monto_facturado,
                'forma_pago' => $registro->forma_pago,
                'nombre_usuario' => $registro->nombre_usuario ?? 'Desconocido',
                'nombre_sede' => $registro->nombre_sede ?? 'Desconocida',
                'fecha_venta' => \Carbon\Carbon::parse($registro->created_at)->format('Y-m-d'),
                'nombre_facultad' => $registro->nombre_facultad ?? 'Desconocida',
            ];
        });

        $this->auditar('cpu_consumo_becado', 'detalleRegistros', '', '', 'CONSULTA', 'Consulta de consumo de alimentos por ayuda económica - Tasty Uleam');

        return response()->json([
            'fecha_inicio' => $fechaInicio->toDateString(),
            'fecha_fin' => $fechaFin->toDateString(),
            'detalles' => $detalles,
        ]);
    }

    // public function enviarCorreo(Request $request, $restanted)
    // {
    //     try {
    //         Log::info('Datos recibidos en enviarCorreo', [
    //             'request_all' => $request->all(),
    //             'restante' => $restanted
    //         ]);

    //         $identificacion = $request->input('identificacion');
    //         $nombresd = $request->input("nombres");
    //         $apellidosd = $request->input("apellidos");
    //         $monto_otorgadod = $request->input('monto_otorgado');
    //         $tipo_alimentos = json_decode($request->input('tipo_alimento'), true);
    //         $monto_facturadod = $request->input('monto_facturado');
    //         $tipo_usuario = $request->input('tipo_usuario');

    //         // Buscar email por identificación
    //         $emaile = null;

    //         $funcionario = CpuFuncionarioComunidad::where('identificacion', $identificacion)->first();
    //         if ($funcionario && $funcionario->email) {
    //             $emaile = $funcionario->email;
    //         } else {
    //             $becado = CpuBecado::where('identificacion', $identificacion)->first();
    //             if ($becado && $becado->email) {
    //                 $emaile = $becado->email;
    //             }
    //         }

    //         if (!$emaile) {
    //             Log::warning('No se encontró email ni en funcionarios ni en becados', ['identificacion' => $identificacion]);
    //             return; // No continúa si no hay email
    //         }

    //         // Construcción del cuerpo del correo
    //         $detallesAlimentos = "<ul>";
    //         foreach ($tipo_alimentos as $alimento) {
    //             $detallesAlimentos .= "<li>" . htmlspecialchars($alimento['descripcion']) . " - Cantidad: " . $alimento['cantidad'] . ", Precio: $" . number_format($alimento['precio'], 2) . "</li>";
    //         }
    //         $detallesAlimentos .= "</ul>";

    //         if ($tipo_usuario === 'Ayuda Económica') {
    //             $asuntoCorreo = "Consumo de alimentos por ayuda económica - Tasty Uleam";
    //             $cuerpoCorreo = "<p>Estimado(a) $apellidosd $nombresd; La EP Uleam, registra el consumo de los siguientes alimentos: $detallesAlimentos Del total de \$$monto_otorgadod dólares, aún tiene disponible \$$restanted dólares. Saludos cordiales.</p>";
    //         } else {
    //             $asuntoCorreo = "Consumo de alimentos - Tasty Uleam";
    //             $cuerpoCorreo = "<p>Estimado(a) $apellidosd $nombresd; La EP Uleam, registra el consumo de los siguientes alimentos: $detallesAlimentos Saludos cordiales.</p>";
    //         }

    //         $persona = [
    //             "destinatarios" => $emaile,
    //             "cc" => "",
    //             "cco" => "",
    //             "asunto" => $asuntoCorreo,
    //             "cuerpo" => $cuerpoCorreo
    //         ];

    //         $datosCodificados = json_encode($persona);
    //         $url = "https://prod-44.westus.logic.azure.com:443/workflows/4046dc46113a4d8bb5da374ef1ee3e32/triggers/manual/paths/invoke?api-version=2016-06-01&sp=%2Ftriggers%2Fmanual%2Frun&sv=1.0&sig=lA40KwffEyLqEjVA4uyHaWAHblO77vk2jXYEkjUG08s";

    //         $ch = curl_init($url);
    //         curl_setopt_array($ch, array(
    //             CURLOPT_CUSTOMREQUEST => "POST",
    //             CURLOPT_POSTFIELDS => $datosCodificados,
    //             CURLOPT_HTTPHEADER => array(
    //                 'Content-Type: application/json',
    //                 'Content-Length' => strlen($datosCodificados),
    //             ),
    //             CURLOPT_RETURNTRANSFER => true,
    //             // CURLOPT_TIMEOUT_MS => 100,
    //             // CURLOPT_CONNECTTIMEOUT_MS => 100,
    //             CURLOPT_SSL_VERIFYPEER => false,
    //             CURLOPT_SSL_VERIFYHOST => false,
    //         ));
    //         $resultado = curl_exec($ch);
    //         Log::info('Correo disparado (sin esperar respuesta)', ['email' => $emaile]);
    //         $codigoRespuesta = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    //         $curlError = curl_error($ch);
    //         curl_close($ch);

    //         if ($codigoRespuesta === 200) {
    //             Log::info('Correo enviado correctamente', ['email' => $emaile]);
    //         } else {
    //             Log::warning('Error al enviar correo', [
    //                 'status' => $codigoRespuesta,
    //                 'curl_error' => $curlError,
    //                 'respuesta' => $resultado,
    //                 'email' => $emaile
    //             ]);
    //         }
    //     } catch (\Exception $e) {
    //         Log::error('Excepción al enviar correo', [
    //             'message' => $e->getMessage(),
    //             'trace' => $e->getTraceAsString(),
    //             'request' => $request->all()
    //         ]);
    //     }
    // }



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
                return;
            }

            // Construcción del cuerpo del correo
            $detallesAlimentos = "<ul>";
            foreach ($tipo_alimentos as $alimento) {
                $detallesAlimentos .= "<li>" . htmlspecialchars($alimento['descripcion']) .
                    " - Cantidad: " . $alimento['cantidad'] .
                    ", Precio: $" . number_format($alimento['precio'], 2) . "</li>";
            }
            $detallesAlimentos .= "</ul>";

            if ($tipo_usuario === 'Ayuda Económica') {
                $asuntoCorreo = "Consumo de alimentos por ayuda económica - Tasty Uleam";
                $cuerpoCorreo = "<p>Estimado(a) $apellidosd $nombresd; La EP Uleam, registra el consumo de los siguientes alimentos: $detallesAlimentos Del total de \$$monto_otorgadod dólares, aún tiene disponible \$$restanted dólares. Saludos cordiales.</p>";
            } else {
                $asuntoCorreo = "Consumo de alimentos - Tasty Uleam";
                $cuerpoCorreo = "<p>Estimado(a) $apellidosd $nombresd; La EP Uleam, registra el consumo de los siguientes alimentos: $detallesAlimentos Saludos cordiales.</p>";
            }

            // 1. Obtener Token de Acceso usando Http Client de Laravel
            $response = Http::withOptions(['verify' => false])->asForm()->post(
                'https://login.microsoftonline.com/31a17900-7589-4cfc-b11a-f4e83c27b8ed/oauth2/v2.0/token',
                [
                    'grant_type' => 'client_credentials',
                    'client_id' => '24e03a5e-0d5b-4c08-8382-bda010b7c3d4',
                    'client_secret' => 'QvD8Q~7K93W8JZUZjFyOvOy2FlS.pBmELA1SNb0S',
                    'scope' => 'https://graph.microsoft.com/.default'
                ]
            );

            $tokenResponse = $response->json();

            if (!isset($tokenResponse['access_token'])) {
                Log::error('Error al obtener token de acceso', ['response' => $tokenResponse]);
                return;
            }

            $accessToken = $tokenResponse['access_token'];

            // 2. Enviar Correo
            $sender = "notificaciones.tasty@uleam.edu.ec";

            $mailUrl = "https://graph.microsoft.com/v1.0/users/$sender/sendMail";

            $mailData = [
                "message" => [
                    "subject" => $asuntoCorreo,
                    "body" => [
                        "contentType" => "html",
                        "content" => $cuerpoCorreo
                    ],
                    "toRecipients" => [
                        [
                            "emailAddress" => ["address" => $emaile]
                        ]
                    ]
                ]
            ];

            $sendResponse = Http::withOptions(['verify' => false])
                ->withToken($accessToken)
                ->post($mailUrl, $mailData);

            if ($sendResponse->successful()) {
                Log::info('Correo enviado correctamente con Microsoft Graph', ['email' => $emaile]);
            } else {
                Log::warning('Error al enviar correo con Microsoft Graph', [
                    'status' => $sendResponse->status(),
                    'response' => $sendResponse->body(),
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

    public function obtenerPeriodos()
    {
        $periodos = CpuBecado::query()
            ->select('periodo')
            ->distinct()
            ->orderBy('periodo', 'desc')
            ->pluck('periodo');

        return response()->json([
            'message' => 'Periodos disponibles',
            'data' => $periodos
        ], 200);
    }


    // public function obtenerResumenBecados(Request $request)
    // {
    //     $request->validate([
    //         'periodo'   => 'required|string',
    //         'page'      => 'nullable|integer|min:1',
    //         'per_page'  => 'nullable|integer|min:1|max:1000',
    //     ]);

    //     $perPage = (int) $request->input('per_page', 15);

    //     $query = CpuBecado::query()
    //         ->select([
    //             'periodo',
    //             'identificacion',
    //             'nombres',
    //             'apellidos',
    //             'sexo',
    //             'beca',
    //             'monto_otorgado',
    //             'monto_consumido',
    //             'matriz_extension',
    //             'facultad',
    //             'carrera',
    //         ])
    //         ->selectRaw('COALESCE(monto_otorgado,0) - COALESCE(monto_consumido,0) AS por_consumir')
    //         ->where('periodo', $request->periodo)
    //         ->orderBy('id', 'asc');

    //     $result = $query->paginate($perPage);

    //     return response()->json([
    //         'message'    => 'Resumen de becados',
    //         'filters'    => [
    //             'periodo' => $request->periodo,
    //         ],
    //         'data'       => $result->items(),
    //         'pagination' => [
    //             'current_page' => $result->currentPage(),
    //             'per_page'     => $result->perPage(),
    //             'total'        => $result->total(),
    //             'last_page'    => $result->lastPage(),
    //         ],
    //     ], 200);
    // }

    public function obtenerResumenBecados(Request $request)
    {
        $request->validate([
            'periodo'   => 'required|string',
            'page'      => 'nullable|integer|min:1',
            'per_page'  => 'nullable|integer|min:1|max:1000',
        ]);

        $perPage = (int) $request->input('per_page', 15);

        $query = CpuBecado::query()
            ->select([
                'periodo',
                'identificacion',
                'nombres',
                'apellidos',
                'sexo',
                'beca',
                'monto_otorgado',
                'monto_consumido',
                'matriz_extension',
                'facultad',
                'carrera',
            ])
            ->selectRaw('COALESCE(monto_otorgado,0) - COALESCE(monto_consumido,0) AS por_consumir')
            ->selectRaw('LEAST(COALESCE(monto_otorgado,0),150) AS monto_otorgado_truncado')
            ->selectRaw('GREATEST(COALESCE(monto_otorgado,0) - 150,0) AS remanente_anterior')
            ->where('periodo', $request->periodo)
            ->orderBy('id', 'asc');

        $result = $query->paginate($perPage);

        return response()->json([
            'message'    => 'Resumen de becados',
            'filters'    => [
                'periodo' => $request->periodo,
            ],
            'data'       => $result->items(),
            'pagination' => [
                'current_page' => $result->currentPage(),
                'per_page'     => $result->perPage(),
                'total'        => $result->total(),
                'last_page'    => $result->lastPage(),
            ],
        ], 200);
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
