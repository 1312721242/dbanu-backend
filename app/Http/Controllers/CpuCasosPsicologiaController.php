<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CpuAtencionPsicologia;
use App\Models\CpuAtencion; // Asegúrate de importar este modelo
use App\Models\CpuCasosPsicologia;
use App\Models\CpuEstados;

class CpuCasosPsicologiaController extends Controller
{
    public function getCasos($tipo_atencion,$usr_tipo, $id_persona)
    {
        try {
            // Iniciar la consulta a la base de datos
            $query = CpuAtencion::where('id_persona', $id_persona)
                                ->where('id_funcionario', $usr_tipo)
                                ->join('cpu_casos', 'cpu_atenciones.id_caso', '=', 'cpu_casos.id')
                                ->select('cpu_casos.id', 'cpu_casos.nombre_caso')
                                ->distinct();

            // Aplicar filtro según el tipo de atención
            if ($tipo_atencion === 'REAPERTURA') {
                $query->where('cpu_casos.id_estado', '9');
            } else {
                $query->where('cpu_casos.id_estado', '8');
            }

            // Obtener los resultados
            $casos = $query->get();

            return response()->json($casos, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al obtener los casos: ' . $e->getMessage()], 500);
        }
    }
}

