<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CpuAtencion;
use App\Models\CpuAtencionTriaje;
use App\Models\CpuDerivacion;
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
            // 'detalle_atencion' => 'required|string',
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

        // Campos opcionales
        $atencion->id_caso = $request->input('id_caso', null);
        $atencion->id_tipo_usuario = $request->input('id_tipo_usuario', null);
        $atencion->evolucion_enfermedad = $request->input('evolucion_enfermedad', '');
        $atencion->diagnostico = $request->input('diagnostico', '');
        $atencion->prescripcion = $request->input('prescripcion', '');
        $atencion->recomendacion = $request->input('recomendacion', '');

        $atencion->save();

        return response()->json(['success' => true, 'id' => $atencion->id]);
    }
//consula de las consultas 
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
}
