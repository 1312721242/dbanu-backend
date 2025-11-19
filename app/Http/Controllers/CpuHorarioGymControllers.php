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
    protected $auditoriaController, $logController;
    public function __construct()
    {
        $this->middleware('auth:api');
        $this->auditoriaController = new AuditoriaControllers();
        $this->logController = new LogController();
    }

    public function getHorarioGym2()
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

    public function getHorarioGym()
    {
        try {
            $data = DB::select(
                "SELECT 
                    h.tg_id,
                    h.tg_hora_apertura,
                    h.tg_hora_cierre,
                    h.tg_json_dias_laborables,
                    h.tg_tipo_servicio,

                    t.ts_nombre AS tipo_servicio_nombre,
                    t.ts_descripcion AS tipo_servicio_descripcion,
                    t.ts_breve_desc AS tipo_servicio_breve_desc,

                    c.cat_id AS categoria_id,
                    c.cat_nombre AS categoria_nombre,
                    c.cat_descripcion AS categoria_descripcion,

                    h.tg_capacidad_maxima,
                    h.tg_tiempo_turno,
                    h.tg_id_estado,

                    e.estado AS nombre_estado,

                    CASE 
                        WHEN h.tg_tipo_usuario = 1 THEN 'Estudiante'
                        WHEN h.tg_tipo_usuario = 2 THEN 'Docente'
                        ELSE 'Desconocido'
                    END AS tipo_usuario_nombre,

                    h.tg_tipo_usuario, 
                    h.tg_created_at,
                    h.tg_updated_at,
                    h.tg_id_user

                FROM db_train_revive.cpu_horarios_gym h
                LEFT JOIN public.cpu_estados e 
                    ON h.tg_id_estado = e.id
                LEFT JOIN db_train_revive.cpu_tipos_servicios t 
                    ON h.tg_tipo_servicio = t.ts_id
                LEFT JOIN db_train_revive.cpu_categoria_servicios c 
                    ON t.ts_id_categoria = c.cat_id order by h.tg_tipo_usuario desc"
            );

            return $data;
        } catch (\Exception $e) {

            $this->logController->saveLog(
                'Controlador: CpuHorarioGymControllers, Función: getHorarioGym()',
                'Error al consultar horarios para asistir al gym: ' . $e->getMessage()
            );

            return response()->json([
                'message' => 'Error al consultar horarios para asistir al gym: ' . $e->getMessage()
            ], 500);
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
                t.ts_nombre AS tipo_servicio_nombre,
                t.ts_breve_desc AS tipo_servicio_breve_desc,
                t.ts_id_categoria,
               
                c.cat_nombre AS categoria_nombre,
                c.cat_descripcion AS categoria_descripcion,

                h.tg_capacidad_maxima,
                h.tg_tiempo_turno,
                h.tg_id_estado,
                 h.tg_tipo_usuario,
                e.estado AS nombre_estado,
                h.tg_created_at,
                h.tg_updated_at,
                h.tg_id_user
            FROM db_train_revive.cpu_horarios_gym h
            LEFT JOIN public.cpu_estados e 
                ON h.tg_id_estado = e.id
            LEFT JOIN db_train_revive.cpu_tipos_servicios t 
                ON h.tg_tipo_servicio = t.ts_id
            LEFT JOIN db_train_revive.cpu_categoria_servicios c 
                ON t.ts_id_categoria = c.cat_id
            WHERE h.tg_id = ?',
                [$id]
            );
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


    public function guardarHorarioGym111(Request $request)
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

    public function guardarHorarioGym(Request $request)
    {
        try {
            DB::beginTransaction();
            $request->validate([
                'tg_hora_apertura'        => 'required',
                'tg_hora_cierre'          => 'required',
                'tg_json_dias_laborables' => 'required',
                'tg_tipo_servicio'        => 'required',
                'tg_capacidad_maxima'     => 'required|integer|min:1',
                'tg_tiempo_turno'         => 'required|integer|min:1',
                'tg_id_estado'            => 'required|integer',
                'tg_tipo_usuario'         => 'required|integer',
            ]);

            $id = $request->tg_id ?? null;

            $oldData = $id
                ? DB::table('db_train_revive.cpu_horarios_gym')->where('tg_id', $id)->first()
                : null;

            $data = [
                'tg_hora_apertura'        => $request->tg_hora_apertura,
                'tg_hora_cierre'          => $request->tg_hora_cierre,
                'tg_json_dias_laborables' => is_string($request->tg_json_dias_laborables)
                    ? $request->tg_json_dias_laborables
                    : json_encode($request->tg_json_dias_laborables),
                'tg_tipo_servicio'        => $request->tg_tipo_servicio,
                'tg_capacidad_maxima'     => $request->tg_capacidad_maxima,
                'tg_tiempo_turno'         => $request->tg_tiempo_turno,
                'tg_id_estado'            => $request->tg_id_estado,
                'tg_tipo_usuario'         => $request->tg_tipo_usuario,
                'tg_updated_at'           => now(),
            ];

            if ($id) {
                DB::table('db_train_revive.cpu_horarios_gym')
                    ->where('tg_id', $id)
                    ->update($data);

                $descripcion = "Se actualizó el horario del Gym con ID $id el " . now();
                $tipoAuditoria = "UPDATE";
            } else {
                $data['tg_created_at'] = now();
                $data['tg_updated_at'] = now();

                $id = DB::table('db_train_revive.cpu_horarios_gym')
                    ->insertGetId($data, 'tg_id');

                $descripcion = "Se registró un nuevo horario del Gym el " . now() . " con ID $id";
                $tipoAuditoria = "INSERT";
            }

            $this->auditoriaController->auditar(
                'cpu_horarios_gym',
                'guardarHorarioGym(Request $request)',
                json_encode($oldData),
                json_encode($data),
                $tipoAuditoria,
                $descripcion
            );

            DB::commit();

            return response()->json([
                "status" => true,
                "message" => $id ? "Horario actualizado correctamente" : "Horario registrado correctamente",
                "tg_id" => $id
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            $this->logController->saveLog(
                'Controlador: CpuHorarioGymControllers, Función: guardarHorarioGym(Request $request)',
                'Error al registrar/actualizar Horarios del Gym: ' . $e->getMessage()
            );

            return response()->json([
                "status" => false,
                "message" => "Error interno del servidor",
                "error"   => $e->getMessage()
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
