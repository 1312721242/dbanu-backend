<?php

namespace App\Http\Controllers;

use App\Models\CpuBecado; // Importa el modelo CpuBecado
use App\Models\CpuConsumoBecado;
use App\Models\CpuConsumoFuncionarioComunidad;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class CpuConsumoBecadoController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth:api');
    }

    // public function registrarConsumo(Request $request)
    // {
    //     $request->validate([
    //         'id_becado' => 'required|integer',
    //         'periodo' => 'required|string',
    //         'identificacion' => 'required|string',
    //         'tipo_alimento' => 'required|string',
    //         'monto_facturado' => 'required|numeric',
    //     ]);

    //     $consumo = new CpuConsumoBecado();
    //     $consumo->id_becado = $request->id_becado;
    //     $consumo->periodo = $request->periodo;
    //     $consumo->identificacion = $request->identificacion;
    //     $consumo->tipo_alimento = $request->tipo_alimento;
    //     $consumo->monto_facturado = $request->monto_facturado;
    //     $consumo->save();

    //     // Actualizar el monto_consumido en la tabla cpu_becados
    //     $becado = CpuBecado::where('id', $request->id_becado)->first();
    //     $becado->monto_consumido += $request->monto_facturado;
    //     $becado->save();

    //     // Llamar a la función enviarCorreo con los datos necesarios
    //     $this->enviarCorreo($request);

    //     return response()->json(['message' => 'Consumo registrado correctamente', 'code' => 200], 200);
    // }

    public function registrarConsumo(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'periodo' => 'required|string',
            'identificacion' => 'required|string',
            'tipo_alimento' => 'required|string',
            'monto_facturado' => 'required|numeric',
            'tipo_usuario' => 'required|string'
        ]);

        if ($request->tipo_usuario === 'becado') {
            $consumo = new CpuConsumoBecado();
            $consumo->id_becado = $request->id;
            $consumo->periodo = $request->periodo;
            $consumo->identificacion = $request->identificacion;
            $consumo->tipo_alimento = $request->tipo_alimento;
            $consumo->monto_facturado = $request->monto_facturado;
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
            $consumo->save();
            $restante = 0;
        }

        // Llamar a la función enviarCorreo con los datos necesarios
        $this->enviarCorreo($request, $restante);

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

        if (is_array($tipo) && $tipo['origen'] === 'Personal Uleam/Otro') {
            $valor = $tipo['valor'];

            if ($valor === 'Todos') {
                // Buscar solo en CpuConsumoFuncionarioComunidad
                $registrosFuncionarios = CpuConsumoFuncionarioComunidad::whereBetween('created_at', [$fechaInicio, $fechaFin])->get();
            } else {
                // Buscar solo en CpuConsumoFuncionarioComunidad donde cargo_puesto coincide
                $registrosFuncionarios = CpuConsumoFuncionarioComunidad::whereBetween('created_at', [$fechaInicio, $fechaFin])
                    ->whereHas('funcionario', function ($query) use ($valor) {
                        $query->where('cargo_puesto', $valor);
                    })->get();
            }

            $registros = $registrosFuncionarios;
        } elseif ($tipo === 'Becado') {
            // Buscar solo en CpuConsumoBecado
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

    if (is_array($tipo) && $tipo['origen'] === 'Personal Uleam/Otro') {
        $valor = $tipo['valor'];

        if ($valor === 'Todos') {
            // Buscar solo en CpuConsumoFuncionarioComunidad
            $registrosFuncionarios = CpuConsumoFuncionarioComunidad::whereBetween('created_at', [$fechaInicio, $fechaFin])
                ->with(['funcionario' => function ($query) {
                    $query->select('id', 'cargo_puesto');
                }])
                ->get();
        } else {
            // Buscar solo en CpuConsumoFuncionarioComunidad donde cargo_puesto coincide
            $registrosFuncionarios = CpuConsumoFuncionarioComunidad::whereBetween('created_at', [$fechaInicio, $fechaFin])
                ->whereHas('funcionario', function ($query) use ($valor) {
                    $query->where('cargo_puesto', $valor);
                })
                ->with(['funcionario' => function ($query) {
                    $query->select('id', 'cargo_puesto');
                }])
                ->get();
        }

        $registros = $registrosFuncionarios;
    } elseif ($tipo === 'Becado') {
        // Buscar solo en CpuConsumoBecado y agregar cargo_puesto como 'Becado'
        $registros = CpuConsumoBecado::whereBetween('created_at', [$fechaInicio, $fechaFin])->get()->map(function ($registro) {
            $registro->cargo_puesto = 'Becado';
            return $registro;
        });
    } else {
        // Buscar en ambas tablas
        $registrosBecados = CpuConsumoBecado::whereBetween('created_at', [$fechaInicio, $fechaFin])->get()->map(function ($registro) {
            $registro->cargo_puesto = 'Becado';
            return $registro;
        });

        $registrosFuncionarios = CpuConsumoFuncionarioComunidad::whereBetween('created_at', [$fechaInicio, $fechaFin])
            ->with(['funcionario' => function ($query) {
                $query->select('id', 'cargo_puesto');
            }])
            ->get();

        $registros = $registrosBecados->concat($registrosFuncionarios);
    }

    // Mapear los detalles de los registros
    $detalles = $registros->map(function ($registro) {
        return [
            // 'periodo' => $registro->periodo,
            'cargo_puesto' => $registro->cargo_puesto ?? $registro->funcionario->cargo_puesto ?? 'Desconocido',
            'identificacion' => $registro->identificacion,
            'tipo_alimento' => $registro->tipo_alimento,
            'monto_facturado' => $registro->monto_facturado,

        ];
    });

    // Log de los detalles que se devuelven
    Log::info('Detalles devueltos en detalleRegistros:', ['detalles' => $detalles]);

    return response()->json([
        'fecha_inicio' => $fechaInicio->toDateString(),
        'fecha_fin' => $fechaFin->toDateString(),
        'detalles' => $detalles,
    ]);
}



    public function enviarCorreo(Request $request, $restanted)
    {
        // Obtener los datos necesarios, ajusta esto según tus necesidades
        // $emaile = $request->input("email");
        $emaile = "p1311836587@dn.uleam.edu.ec"; // Asumiendo que quieras cambiar esto por $request->input("email") más tarde
        $nombresd = $request->input("nombres");
        $apellidosd = $request->input("apellidos");
        $monto_otorgadod = $request->input('monto_otorgado');
        // $restanted = $restante->input('restante');
        $tipo_alimentos = json_decode($request->input('tipo_alimento'), true);
        $monto_facturadod = $request->input('monto_facturado');
        $tipo_usuario = $request->input('tipo_usuario');

        // Construir la lista de alimentos
        $detallesAlimentos = "<ul>";
        foreach ($tipo_alimentos as $alimento) {
            $detallesAlimentos .= "<li>" . htmlspecialchars($alimento['descripcion']) . " - Cantidad: " . $alimento['cantidad'] . ", Precio: $" . number_format($alimento['precio'], 2) . "</li>";
        }
        $detallesAlimentos .= "</ul>";

        // Crear cuerpo del correo basado en el tipo de usuario
        if ($tipo_usuario === 'becado') {
            $cuerpoCorreo = "<p>Estimado(a) $apellidosd $nombresd; La EPE Uleam, registra el consumo de los siguientes alimentos: $detallesAlimentos Del total de \$$monto_otorgadod dólares, aún tiene disponible \$$restanted dólares. Saludos cordiales.</p>";
        } else {
            $cuerpoCorreo = "<p>Estimado(a) $apellidosd $nombresd; La EPE Uleam, registra el consumo de los siguientes alimentos: $detallesAlimentos Saludos cordiales.</p>";
        }

        $persona = [
            "destinatarios" => $emaile,
            "cc" => "",
            "cco" => "",
            "asunto" => "Consumo de alimentos por ayuda económica - Tasty Uleam",
            "cuerpo" => $cuerpoCorreo
        ];

        // Codificar los datos
        $datosCodificados = json_encode($persona);

        // URL de destino
        $url = "https://prod-44.westus.logic.azure.com:443/workflows/4046dc46113a4d8bb5da374ef1ee3e32/triggers/manual/paths/invoke?api-version=2016-06-01&sp=%2Ftriggers%2Fmanual%2Frun&sv=1.0&sig=lA40KwffEyLqEjVA4uyHaWAHblO77vk2jXYEkjUG08s";

        // Inicializar cURL
        $ch = curl_init($url);

        // Configurar opciones de cURL
        curl_setopt_array($ch, array(
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $datosCodificados,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($datosCodificados),
                'Personalizado: ¡Hola mundo!',
            ),
            CURLOPT_RETURNTRANSFER => true,
        ));

        // Realizar la solicitud cURL
        $resultado = curl_exec($ch);
        $codigoRespuesta = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Procesar la respuesta
        if ($codigoRespuesta === 200) {
            $respuestaDecodificada = json_decode($resultado);
            return response()->json(['message' => 'Solicitud enviada correctamente'], 200);
        } else {
            // Manejar errores
            return response()->json(['error' => "Error consultando. Código de respuesta: $codigoRespuesta"], $codigoRespuesta);
        }
    }
}
