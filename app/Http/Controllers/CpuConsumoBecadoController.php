<?php

namespace App\Http\Controllers;

use App\Models\CpuBecado; // Importa el modelo CpuBecado
use App\Models\CpuConsumoBecado;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class CpuConsumoBecadoController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function registrarConsumo(Request $request)
    {
        $request->validate([
            'id_becado' => 'required|integer',
            'periodo' => 'required|string',
            'identificacion' => 'required|string',
            'tipo_alimento' => 'required|string',
            'monto_facturado' => 'required|numeric',
        ]);

        $consumo = new CpuConsumoBecado();
        $consumo->id_becado = $request->id_becado;
        $consumo->periodo = $request->periodo;
        $consumo->identificacion = $request->identificacion;
        $consumo->tipo_alimento = $request->tipo_alimento;
        $consumo->monto_facturado = $request->monto_facturado;
        $consumo->save();

        // Actualizar el monto_consumido en la tabla cpu_becados
        $becado = CpuBecado::where('id', $request->id_becado)->first();
        $becado->monto_consumido += $request->monto_facturado;
        $becado->save();

        // Llamar a la función enviarCorreo con los datos necesarios
        $this->enviarCorreo($request);

        return response()->json(['message' => 'Consumo registrado correctamente', 'code' => 200], 200);
    }

    public function registrosPorFechas($fechaInicio, $fechaFin)
    {
        // Convertir las fechas a objetos Carbon para facilitar la comparación y manipulación
        $fechaInicio = Carbon::parse($fechaInicio);
        $fechaFin = Carbon::parse($fechaFin);

        // Si las fechas son iguales, buscar solo en esa fecha
        if ($fechaInicio->isSameDay($fechaFin)) {
            $registros = CpuConsumoBecado::whereDate('created_at', $fechaInicio)->get();
        } else {
            // Si las fechas no son iguales, buscar en el rango de fechas
            $registros = CpuConsumoBecado::whereBetween('created_at', [$fechaInicio, $fechaFin])->get();
        }

        $total_por_tipo = $registros->groupBy('tipo_alimento')
            ->map(function ($items) {
                return [
                    'total' => $items->count(),
                    'valor_vendido' => $items->sum('monto_facturado'),
                ];
            });

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
    public function detalleRegistros($fechaInicio, $fechaFin)
    {
        $registros = CpuConsumoBecado::whereBetween('created_at', [$fechaInicio, $fechaFin])->get();

        $detalles = $registros->map(function ($registro) {
            return [
                'periodo' => $registro->periodo,
                'identificacion' => $registro->identificacion,
                'tipo_alimento' => $registro->tipo_alimento,
                'monto_facturado' => $registro->monto_facturado,
            ];
        });

        return response()->json([
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
            'detalles' => $detalles,
        ]);
    }

    public function enviarCorreo(Request $request)
    {
        // Obtener los datos necesarios, ajusta esto según tus necesidades
        // $emaile = $request->input("email");
        $emaile = "p1311836587@dn.uleam.edu.ec";
        $nombresd = $request->input("nombres");
        $apellidosd = $request->input("apellidos");
        $monto_otorgadod = $request->input('monto_otorgado');
        $restanted = $request->input('restante');
        $tipo_alimentod = $request->input('tipo_alimento');
        $monto_facturadod = $request->input('monto_facturado');

        $persona = [
            "destinatarios" => $emaile,
            "cc" => "",
            "cco" => "",
            "asunto" => "Consumo de alimentos por ayuda económica - Tasty Uleam",
            "cuerpo" => "<p>Estimado(a) estudiante; La EPE Uleam, registra el consumo de $tipo_alimentod por un valor de $$monto_facturadod dólares; del total de $$monto_otorgadod dólaes, aún tiene disponible $$restanted dolares, saludos cordiales.</p>"
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
            // Realiza acciones adicionales si es necesario
            $array[0] = 1;
        } else {
            // Manejar errores
            return response()->json(['error' => "Error consultando. Código de respuesta: $codigoRespuesta"], $codigoRespuesta);
        }

        // Devolver una respuesta
        return response()->json(['message' => 'Solicitud enviada correctamente'], 200);
    }

}
