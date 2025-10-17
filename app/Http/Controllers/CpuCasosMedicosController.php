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

    public function cerrarCasoConTurnos(Request $request)
    {
        $data = $request->validate([
            'id_caso'       => 'required|integer',
            'informe_final' => 'nullable|string',
            'forzar'        => 'nullable|boolean',
        ]);

        $idCaso       = (int) $data['id_caso'];
        $informeFinal = $data['informe_final'] ?? '';
        $forzar       = (bool)($data['forzar'] ?? false);

        try {
            // Llamar a la función de PostgreSQL
            $rows = DB::select('SELECT * FROM cerrar_caso_con_turnos(?, ?, ?)', [
                $idCaso,
                $informeFinal,
                $forzar
            ]);

            $row = $rows[0] ?? null;
            if (!$row) {
                return response()->json([
                    'success' => false,
                    'need_confirm' => false,
                    'msg' => 'Sin respuesta de la función.',
                    'turnos' => [],
                ], 500);
            }

            // Normalizar respuesta JSON
            $payload = [
                'success'      => (bool)$row->success,
                'need_confirm' => (bool)$row->need_confirm,
                'msg'          => (string)$row->msg,
                'turnos'       => json_decode($row->turnos ?? '[]', true),
            ];

            // ⚠️ Opcional: si prefieres enviar 409 (conflicto) cuando hay turnos y no se forzó
            // if ($payload['need_confirm']) {
            //     return response()->json($payload, 409);
            // }

            return response()->json($payload, 200);
        } catch (\Exception $e) {
            Log::error("Error al cerrar caso", [
                'id_caso' => $idCaso,
                'error'   => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'need_confirm' => false,
                'msg' => 'Error en el servidor',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
