<?php

namespace App\Http\Controllers;

use App\Models\CpuAspirantesEvaluaciones;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CpuAspirantesEvaluacionesController extends Controller
{
    public function getEvaluaciones(Request $request)
    {
        $request->validate([
            'periodo' => 'required|string',
            'identifica' => 'required|string',
        ]);

        $periodo = $request->input('periodo');
        $identifica = $request->input('identifica');

        $evaluaciones = CpuAspirantesEvaluaciones::where('periodo', $periodo)
            ->where('identifica', $identifica)
            ->select(
                'periodo',
                'identifica',
                'apellidos',
                'nombres',
                'sede',
                'carrera',
                'tipo_evalu',
                'grupo_ca',
                'zona',
                'bloque',
                'sala',
                'fecha',
                'horario',
                'cod_lugar'
            )
            ->get();

        // Auditoría
        $this->auditar('cpu_aspirantes_evaluaciones', 'getEvaluaciones', '', '', 'CONSULTA', "CONSULTA DE EVALUACIONES PARA PERIODO: $periodo, IDENTIFICA: $identifica");

        return response()->json($evaluaciones);
    }

    public function getEvaluacionesCedula(Request $request)
    {
        $query = CpuAspirantesEvaluaciones::query();

        if ($request->has('identifica') && $request->input('identifica') != '') {
            $query->where('identifica', 'like', '%' . $request->input('identifica') . '%');
        }

        if ($request->has('nombres') && $request->input('nombres') != '') {
            $query->where('nombres', 'like', '%' . $request->input('nombres') . '%');
        }

        if ($request->has('apellidos') && $request->input('apellidos') != '') {
            $query->where('apellidos', 'like', '%' . $request->input('apellidos') . '%');
        }

        $evaluaciones = $query->select(
            'periodo',
            'identifica',
            'apellidos',
            'nombres',
            'sede',
            'carrera',
            'tipo_evalu',
            'grupo_ca',
            'zona',
            'bloque',
            'sala',
            'fecha',
            'horario',
            'cod_lugar'
        )
            ->get();

        // Auditoría
        $this->auditar('cpu_aspirantes_evaluaciones', 'getEvaluacionesCedula', '', '', 'CONSULTA', "CONSULTA DE EVALUACIONES POR CEDULA");

        return response()->json($evaluaciones);
    }

    public function updateAsistencia(Request $request)
    {
        $request->validate([
            'identifica' => 'required|string|exists:cpu_aspirantes_evaluaciones,identifica',
            'asistencia' => 'required|boolean',
            'fecha_asistencia' => 'required|date'
        ]);

        $identifica = $request->input('identifica');
        $asistencia = $request->input('asistencia');
        $fecha_asistencia = $request->input('fecha_asistencia');

        $evaluacion = CpuAspirantesEvaluaciones::where('identifica', $identifica)->firstOrFail();
        $evaluacion->asistencia = $asistencia;
        $evaluacion->fecha_asistencia = $fecha_asistencia;
        $evaluacion->save();

        // Auditoría
        $this->auditar('cpu_aspirantes_evaluaciones', 'updateAsistencia', '', $identifica, 'MODIFICACION', "ACTUALIZACION DE ASISTENCIA PARA IDENTIFICA: $identifica");

        return response()->json(['message' => 'Asistencia actualizada correctamente']);
    }

    public function getReporteEvaluaciones(Request $request)
    {
        $request->validate([
            'fecha_inicio' => 'required|date_format:Y-m-d',
            'fecha_fin' => 'required|date_format:Y-m-d',
        ]);

        $fechaInicio = $request->input('fecha_inicio');
        $fechaFin = $request->input('fecha_fin');

        $periodo = $request->input('periodo');
        $identifica = $request->input('identifica');

        // Consulta para obtener las evaluaciones segmentadas y contadas por rango de fecha
        $query = CpuAspirantesEvaluaciones::select(
            'sede',
            DB::raw('SUM(CASE WHEN asistencia = 1 THEN 1 ELSE 0 END) AS asistentes'),
            DB::raw('SUM(CASE WHEN asistencia = 0 THEN 1 ELSE 0 END) AS ausentes')
        )
        ->whereBetween('fecha_formateada', [$fechaInicio, $fechaFin]);

        // Aplicar filtros opcionales si están presentes
        if ($periodo) {
            $query->where('periodo', $periodo);
        }

        if ($identifica) {
            $query->where('identifica', $identifica);
        }

        $evaluaciones = $query->groupBy('sede')->get();

        // Auditoría
        $this->auditar('cpu_aspirantes_evaluaciones', 'getReporteEvaluaciones', '', '', 'CONSULTA', "REPORTE DE EVALUACIONES DESDE: $fechaInicio HASTA: $fechaFin");

        return response()->json($evaluaciones);
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
