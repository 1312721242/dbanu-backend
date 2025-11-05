<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Session;
use Carbon;
use Auth;

class CpuHorarioGymControllers extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
        $this->auditoriaController = new AuditoriaControllers();
        $this->logController = new LogController();
    }

    public function getHorarioGym()
    {
        try {
            $data = DB::select(
                'SELECT 
                    h.tg_id,
                    h.tg_hora_apertura,
                    h.tg_hora_cierre,
                    h.tg_json_dias_laborables,
                    h.tg_tipo_servicio,
                    t.ts_descripcion AS tipo_servicio_descripcion,
                    h.tg_capacidad_maxima,
                    h.tg_tiempo_turno,
                    h.tg_id_estado,
                    e.estado AS nombre_estado,
                    h.tg_created_at,
                    h.tg_updated_at,
                    h.tg_id_user
                FROM db_train_revive.cpu_horarios_gym h
                LEFT JOIN public.cpu_estados e ON h.tg_id_estado = e.id
                LEFT JOIN db_train_revive.cpu_tipos_servicios t ON h.tg_tipo_servicio = t.ts_id'
            );

            return $data;
        } catch (\Exception $e) {
            $this->logController->saveLog(
                'Controlador: CpuHorarioGymControllers, Función: getHorarioGym()',
                'Error al consultar horarios para asistir al gym: ' . $e->getMessage()
            );
            return response()->json(['message' => 'Error al consultar horarios para asistir al gym: ' . $e->getMessage()], 500);
        }
    }

    public function getHorarioGymId($id)
    {
        try {
            $data = DB::select(
                'SELECT 
                h.tg_id,
                h.tg_hora_apertura,
                h.tg_hora_cierre,
                h.tg_json_dias_laborables,
                h.tg_tipo_servicio,
                t.ts_descripcion AS tipo_servicio_descripcion,
                h.tg_capacidad_maxima,
                h.tg_tiempo_turno,
                h.tg_id_estado,
                e.estado AS nombre_estado,
                h.tg_created_at,
                h.tg_updated_at,
                h.tg_id_user
            FROM db_train_revive.cpu_horarios_gym h
            LEFT JOIN public.cpu_estados e ON h.tg_id_estado = e.id
            LEFT JOIN db_train_revive.cpu_tipos_servicios t ON h.tg_tipo_servicio = t.ts_id
            WHERE h.tg_id = ?',
                [$id]  
            );

            // Retornar solo un registro (si existe)
            if (count($data) > 0) {
                return response()->json(['success' => true, 'data' => $data[0]]);
            } else {
                return response()->json(['success' => false, 'message' => 'Horario no encontrado']);
            }
        } catch (\Exception $e) {
            $this->logController->saveLog(
                'Controlador: CpuHorarioGymControllers, Función: getHorarioGymId()',
                'Error al consultar horario por ID: ' . $e->getMessage()
            );
            return response()->json(['success' => false, 'message' => 'Error al consultar horario: ' . $e->getMessage()], 500);
        }
    }


    public function guardarHorarioGym(Request $request)
    {
        try {
            $request->validate([
                'hora_apertura'     => 'required|date_format:H:i',
                'hora_cierre'       => 'required|date_format:H:i|after:hora_apertura',
                'dias_semana'       => 'required|array',
                'select_estado'     => 'required|integer',
                'capacidad_maxima'  => 'required|integer|min:1',
                'tiempo_sesion'     => 'required|integer|min:1',
                'servicio_id'     => 'required|integer',
            ]);

            $id = DB::table('db_train_revive.cpu_horarios_gym')->insertGetId([
                'tg_hora_apertura'       => $request->hora_apertura,
                'tg_hora_cierre'         => $request->hora_cierre,
                'tg_json_dias_laborables' => json_encode($request->dias_semana),
                'tg_tipo_servicio'       => $request->servicio_id,
                'tg_capacidad_maxima'    => $request->capacidad_maxima,
                'tg_tiempo_turno'        => $request->tiempo_sesion,
                'tg_id_estado'           => $request->select_estado,
                'tg_id_user'             => Auth::id(),
                'tg_tipo_usuario'        => $request->select_tipo_usuario,
                'tg_created_at'          => now(),
                'tg_updated_at'          => now(),
            ], 'tg_id');

            // Auditoría
            $descripcionAuditoria = 'Se registró el horario de: '
                . $request->servicio_id
                . ' el: ' . now()
                . ' con ID: ' . $id;

            $this->auditoriaController->auditar(
                'cpu_horarios_gym',
                'guardarHorarioGym(Request $request)',
                '',
                $request->all(),
                'INSERT',
                $descripcionAuditoria
            );

            // Respuesta exitosa
            return response()->json([
                'success' => true,
                'mensaje' => 'Horario registrado correctamente',
                'id' => $id
            ], 201);
        } catch (\Exception $e) {
            // Guardado de log en caso de error
            $this->logController->saveLog(
                'Controlador: CpuHorarioGymControllers, Función: guardarHorarioGym(Request $request)',
                'Error al registrar Horarios del Gym: ' . $e->getMessage()
            );

            // Respuesta de error
            return response()->json([
                'message' => 'Error al registrar Horarios del Gym: ' . $e->getMessage()
            ], 500);
        }
    }


    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
