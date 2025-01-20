<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CpuDerivacion;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CpuDerivacionController extends Controller
{
    public function index()
    {
        $derivaciones = CpuDerivacion::all();
        return response()->json($derivaciones);
    }

    public function show($id)
    {
        $derivacion = CpuDerivacion::find($id);

        if (!$derivacion) {
            return response()->json(['error' => 'Derivación no encontrada'], 404);
        }

        return response()->json($derivacion);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'ate_id' => 'required|integer|exists:cpu_atenciones,id',
            'id_doctor_al_que_derivan' => 'required|integer|exists:users,id',
            'id_paciente' => 'required|integer|exists:cpu_personas,id',
            'motivo_derivacion' => 'required|string',
            'detalle_derivacion' => 'required|string',
            'id_area' => 'required|integer',
            'fecha_para_atencion' => 'required|date',
            'hora_para_atencion' => 'required|date_format:H:i:s',
            'id_estado_derivacion' => 'integer|exists:cpu_estados,id',
            'id_turno_asignado' => 'required|integer|exists:cpu_turnos,id_turnos',
        ]);

        $data['id_funcionario_que_derivo'] = Auth::id();
        $data['fecha_derivacion'] = Carbon::now();

        $derivacion = CpuDerivacion::create($data);

        if ($derivacion) {
            // Actualizar la tabla de turnos
            DB::table('cpu_turnos')
                ->where('id_turnos', $data['id_turno_asignado'])
                ->update([
                    'estado' => $data['id_estado_derivacion'],
                    'id_paciente' => $data['id_paciente']
                ]);
        }

        return response()->json($derivacion, 201);
    }

    public function update(Request $request, $id)
    {
        $derivacion = CpuDerivacion::find($id);

        if (!$derivacion) {
            return response()->json(['error' => 'Derivación no encontrada'], 404);
        }

        $data = $request->validate([
            'ate_id' => 'integer|exists:cpu_atenciones,id',
            'id_doctor_al_que_derivan' => 'integer|exists:users,id',
            'id_paciente' => 'integer|exists:cpu_personas,id',
            'motivo_derivacion' => 'string',
            'detalle_derivacion' => 'required|string',
            'id_area' => 'integer',
            'fecha_para_atencion' => 'date',
            'hora_para_atencion' => 'date_format:H:i:s',
            'id_estado_derivacion' => 'integer|exists:cpu_estados,id',
            'id_turno_asignado' => 'integer|exists:cpu_turnos,id_turnos',
        ]);

        $derivacion->update($data);

        return response()->json($derivacion);
    }

    public function destroy($id)
    {
        $derivacion = CpuDerivacion::find($id);

        if (!$derivacion) {
            return response()->json(['error' => 'Derivación no encontrada'], 404);
        }

        $derivacion->delete();

        return response()->json(['success' => true]);
    }

    // Método para obtener derivaciones por doctor y rango de fechas
    public function getDerivacionesByDoctorAndDate(Request $request)
    {
        // Validar los parámetros de entrada
        $request->validate([
            'doctor_id' => 'required|integer|exists:users,id',
            'user_id' => 'required|integer|exists:users,id',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date',
        ]);

        // Obtener los parámetros de la solicitud
        $doctorId = $request->input('doctor_id');
        $userId = $request->input('user_id');
        $fechaInicio = Carbon::parse($request->input('fecha_inicio'))->startOfDay();
        $fechaFin = Carbon::parse($request->input('fecha_fin'))->endOfDay();

        // Crear la consulta base
        $query = CpuDerivacion::with(['paciente', 'funcionarioQueDerivo'])
            ->whereBetween('fecha_para_atencion', [$fechaInicio, $fechaFin])
            ->select(
                'cpu_personas.id as id_paciente',
                'cpu_personas.cedula',
                'cpu_personas.nombres',
                'cpu_personas.id_clasificacion_tipo_usuario',
                'cpu_derivaciones.id as id_derivacion',
                'cpu_derivaciones.fecha_para_atencion',
                'cpu_derivaciones.motivo_derivacion',
                'users.name as funcionario_que_deriva',
                'cpu_derivaciones.hora_para_atencion',
                'cpu_derivaciones.id_estado_derivacion'
            )
            ->join('cpu_personas', 'cpu_personas.id', '=', 'cpu_derivaciones.id_paciente')
            ->join('users', 'users.id', '=', 'cpu_derivaciones.id_funcionario_que_derivo');

        // Agregar las condiciones según el doctor_id
        // if ($doctorId == 9) {
        //     $query->where('id_estado_derivacion', 7);
        // } elseif ($doctorId != 1) {
        //     $query->where('id_doctor_al_que_derivan', $doctorId);
        // }

        // // Agregar la condición para user_id si doctor_id no es 1 o 9
        // if ($doctorId != 1 && $doctorId != 9) {
        //     $query->where('id_funcionario_que_derivo', $userId);
        // }

        // Ordenar los resultados por fecha y hora ascendentemente
        $query->orderBy('fecha_para_atencion', 'asc')
            ->orderBy('hora_para_atencion', 'asc');

        // Ejecutar la consulta
        $derivaciones = $query->get();

        // Devolver las derivaciones como respuesta JSON
        return response()->json($derivaciones);
    }


    // Método para obtener derivaciones por doctor y rango de fechas
    public function getDerivacionesAll(Request $request)
    {
        // Validar los parámetros de entrada
        $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date',
        ]);

        // Obtener los parámetros de la solicitud
        $fechaInicio = Carbon::parse($request->input('fecha_inicio'))->startOfDay();
        $fechaFin = Carbon::parse($request->input('fecha_fin'))->endOfDay();

        // Consultar las derivaciones según los criterios
        $derivaciones = CpuDerivacion::with(['paciente', 'funcionarioQueDerivo'])
            ->whereBetween('fecha_para_atencion', [$fechaInicio, $fechaFin])
            ->select(
                'cpu_personas.id as id_paciente',
                'cpu_personas.cedula',
                'cpu_personas.nombres',
                'cpu_derivaciones.id as id_derivacion',
                'cpu_derivaciones.fecha_para_atencion',
                'cpu_derivaciones.motivo_derivacion',
                'users.name as funcionario_que_deriva',
                'cpu_derivaciones.hora_para_atencion'
            )
            ->join('cpu_personas', 'cpu_personas.id', '=', 'cpu_derivaciones.id_paciente')
            ->join('users', 'users.id', '=', 'cpu_derivaciones.id_funcionario_que_derivo')
            ->get();

        // Devolver las derivaciones como respuesta JSON
        return response()->json($derivaciones);
    }
}
