<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CpuAtencion;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CpuAtencionesController extends Controller
{
    public function __construct()
    {
        // Establecer el idioma a español globalmente
        Carbon::setLocale('es');
        // Configurar el locale de PHP a español
        setlocale(LC_TIME, 'es_ES.UTF-8');
    }

    public function guardarAtencion(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_funcionario' => 'required|integer',
            'id_persona' => 'required|integer',
            'via_atencion' => 'required|string',
            'motivo_atencion' => 'required|string',
            'detalle_atencion' => 'required|string',
            'fecha_hora_atencion' => 'required|date_format:Y-m-d H:i:s',
            'anio_atencion' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $atencion = new CpuAtencion();
        $atencion->id_funcionario = $request->input('id_funcionario');
        $atencion->id_persona = $request->input('id_persona');
        $atencion->via_atencion = $request->input('via_atencion');
        $atencion->motivo_atencion = $request->input('motivo_atencion');
        $atencion->detalle_atencion = $request->input('detalle_atencion');
        $atencion->fecha_hora_atencion = $request->input('fecha_hora_atencion');
        $atencion->anio_atencion = $request->input('anio_atencion');
        $atencion->save();

        return response()->json(['success' => true]);
    }

    // Consulta de las atenciones
    public function obtenerAtencionesPorPaciente($id_persona, $id_funcionario)
    {
        // Realiza la consulta filtrando por id_persona y id_funcionario y selecciona todas las columnas necesarias
        $atenciones = DB::table('cpu_atenciones as at')
            ->select(
                'at.id',
                'at.id_funcionario',
                'at.id_persona',
                'at.via_atencion',
                'at.motivo_atencion',
                'at.fecha_hora_atencion',
                'at.anio_atencion',
                'at.created_at',
                'at.updated_at',
                'at.detalle_atencion'
            )
            ->when($id_persona, function ($query, $id_persona) {
                return $query->where('at.id_persona', $id_persona);
            })
            ->when($id_funcionario, function ($query, $id_funcionario) {
                return $query->where('at.id_funcionario', $id_funcionario);
            })
            // Ordena por fecha de creación de forma descendente
            ->orderBy('at.created_at', 'desc')
            ->get();

        // Formatear la fecha después de obtener los datos
        $atenciones->transform(function ($atencion) {
            $atencion->fecha_hora_atencion = Carbon::parse($atencion->fecha_hora_atencion)->translatedFormat('l, d F Y');
            return $atencion;
        });

        // Log information
        Log::info('Atenciones obtenidas:', ['atenciones' => $atenciones]);

        // Retorna la respuesta en formato JSON
        return response()->json($atenciones);
    }

    public function eliminarAtencion($atencionId)
    {
        try {
            DB::table('cpu_atenciones')->where('id', $atencionId)->delete();
            return response()->json(['message' => 'Atención eliminada correctamente']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al eliminar la atención'], 500);
        }
    }

    public function obtenerUltimaConsulta($usr_tipo, $id_persona, $id_caso)
    {
        try {
            // Busca la última atención del paciente con el id_persona e id_caso especificados
            $ultimaConsulta = CpuAtencion::where('id_persona', $id_persona)
                ->where('id_funcionario', $usr_tipo)
                ->where('id_caso', $id_caso)
                ->orderBy('fecha_hora_atencion', 'desc')
                ->first();

            // Si se encuentra una consulta
            if ($ultimaConsulta) {
                // Formatear la fecha para mostrar el día de la semana y el nombre completo del mes en español
                $ultimaConsulta->fecha_hora_atencion = Carbon::parse($ultimaConsulta->fecha_hora_atencion)->translatedFormat('l, d F Y');
            } else {
                return response()->json(['mensaje' => 'No se encontraron consultas para el paciente con el caso especificado'], 404);
            }

            // Devuelve la última consulta encontrada
            return response()->json($ultimaConsulta, 200);
        } catch (\Exception $e) {
            // Maneja cualquier error que ocurra durante la ejecución
            return response()->json(['error' => 'Error al obtener la última consulta: ' . $e->getMessage()], 500);
        }
    }
}
