<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CpuCasosMedicosController extends Controller
{
    /**
     * Obtener los casos abiertos (con atenciones) de un funcionario
     */
    public function getCasosAbiertos(Request $request, $id_funcionario)
    {
        try {
            // Llamar a la función en PostgreSQL
            $result = DB::select("SELECT * FROM get_casos_abiertos_con_atenciones(?)", [$id_funcionario]);

            // PostgreSQL devuelve cada fila como objeto stdClass con campo JSONB
            // Necesitamos decodificarlo para entregarlo como JSON válido
            $casos = array_map(function ($row) {
                return json_decode(json_encode($row), true);
            }, $result);

            return response()->json([
                'success' => true,
                'funcionario_id' => $id_funcionario,
                'casos' => array_map(fn($c) => json_decode(array_values($c)[0], true), $casos)
            ], 200);

        } catch (\Exception $e) {
            Log::error("Error al obtener casos abiertos", [
                'funcionario_id' => $id_funcionario,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al consultar casos abiertos',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
