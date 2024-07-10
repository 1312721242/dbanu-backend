<?php

namespace App\Http\Controllers;
use App\Models\CpuBecado; // Importa el modelo CpuBecado
use App\Models\CpuConsumoBecado;
use Illuminate\Http\Request;

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

        return response()->json(['message' => 'Consumo registrado correctamente', 'code' => 200], 200);
    }
    //buscar consolidado por fechas
    public function registrosPorFecha($fecha)
    {
        $registros = CpuConsumoBecado::whereDate('created_at', $fecha)->get();

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
            'fecha' => $fecha,
            'total_por_tipo' => $total_por_tipo,
            'total_global' => $total_global,
        ]);
    }
    //buscar solo por fechas
    public function detalleRegistro($fecha)
    {
        $registros = CpuConsumoBecado::whereDate('created_at', $fecha)->get();

        $detalles = $registros->map(function ($registro) {
            return [
                'periodo' => $registro->periodo,
                'identificacion' => $registro->identificacion,
                'tipo_alimento' => $registro->tipo_alimento,
                'monto_facturado' => $registro->monto_facturado,
            ];
        });

        return response()->json([
            'fecha' => $fecha,
            'detalles' => $detalles,
        ]);
    }


}
