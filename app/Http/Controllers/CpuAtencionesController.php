<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CpuAtencion;
use App\Models\CpuAtencionTriaje;
use App\Models\CpuDerivacion;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB; // Asegúrate de importar esta clase
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CpuAtencionesController extends Controller
{
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
        $atencion->fecha_hora_atencion = Carbon::parse($atencion->fecha_hora_atencion)->format('Y-m-d');
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

    public function guardarAtencionConTriaje(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_funcionario' => 'required|integer',
            'id_paciente' => 'required|integer',
            'id_derivacion' => 'required|integer|exists:cpu_derivaciones,id',
            'id_estado_derivacion' => 'required|integer|exists:cpu_derivaciones,id_estado_derivacion',
            'talla' => 'required|integer',
            'peso' => 'required|numeric',
            'temperatura' => 'required|numeric',
            'presion_sistolica' => 'required|integer',
            'presion_diastolica' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        // Iniciar una transacción
        DB::beginTransaction();

        try {
            // Actualizar la derivación
            $derivacion = CpuDerivacion::findOrFail($request->input('id_derivacion'));
            $derivacion->id_estado_derivacion = 18;
            $derivacion->save();

            // Guardar la atención
            $atencion = new CpuAtencion();
            $atencion->id_funcionario = $request->input('id_funcionario');
            $atencion->id_persona = $request->input('id_paciente');
            $atencion->via_atencion = 'Presencial';
            $atencion->motivo_atencion = 'Triaje';
            $atencion->detalle_atencion = 'Signos Vitales';
            $atencion->fecha_hora_atencion = Carbon::now();
            $atencion->anio_atencion = Carbon::now()->year;
            $atencion->save();

            // Guardar el triaje
            $triaje = new CpuAtencionTriaje();
            $triaje->id_derivacion = $request->input('id_derivacion');
            $triaje->talla = $request->input('talla');
            $triaje->peso = $request->input('peso');
            $triaje->temperatura = $request->input('temperatura');
            $triaje->presion_sistolica = $request->input('presion_sistolica');
            $triaje->presion_diastolica = $request->input('presion_diastolica');
            $triaje->save();

            // Confirmar la transacción
            DB::commit();

            return response()->json(['success' => true, 'atencion_id' => $atencion->id, 'triaje_id' => $triaje->id]);
        } catch (\Exception $e) {
            // Deshacer la transacción en caso de error
            DB::rollBack();
            Log::error('Error al guardar la atención y el triaje:', ['exception' => $e->getMessage()]);
            return response()->json(['error' => 'Error al guardar la atención y el triaje'], 500);
        }
    }
}
