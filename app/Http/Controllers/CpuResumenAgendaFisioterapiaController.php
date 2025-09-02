<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CpuResumenAgendaFisioterapiaController extends Controller
{
    /**
     * Consultar el resumen de agenda de fisioterapia de un paciente.
     */
    public function resumen(Request $request)
    {
        $request->validate([
            'id_paciente' => 'required|integer',
            'id_medico'   => 'nullable|integer',
        ]);

        $idPaciente = $request->input('id_paciente');
        $idMedico   = $request->input('id_medico');

        try {
            // Llamar a la función en PostgreSQL
            $resultado = DB::select("
                SELECT *
                FROM public.resumen_agenda_fisioterapia(:idPaciente, :idMedico)
            ", [
                'idPaciente' => $idPaciente,
                'idMedico'   => $idMedico
            ]);

            if (empty($resultado)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró información de agenda para este paciente.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data'    => $resultado[0],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
