<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CpuTurno;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class TurnosGymControllers extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
        $this->auditoriaController = new AuditoriaControllers();
        $this->logController = new LogController();
    }

    public function getTurnoGym()
    {
        try {
            $data = DB::select("
                SELECT
                t.tg_id,
                t.tg_fecha,
                TO_CHAR(t.tg_fecha, 'HH24:MI') AS tg_hora_2,
                t.tg_hora,
                t.tg_id_servicio,
                t.tg_id_estado,
                t.tg_id_user,
                t.tg_created_at,
                h.tg_hora_apertura,
                h.tg_hora_cierre,
                h.tg_capacidad_maxima,
                h.tg_tiempo_turno,
                e.estado   AS estado_nombre,
                u.name,
                ts.ts_descripcion
            FROM db_train_revive.cpu_turnos_gym      AS t
            JOIN db_train_revive.cpu_horarios_gym    AS h
                ON t.tg_id_horario_gym = h.tg_id
            LEFT JOIN public.users  AS u
                ON t.tg_id_user = u.id
            LEFT JOIN public.cpu_estados AS e
                ON t.tg_id_estado = e.id
            LEFT JOIN db_train_revive.cpu_tipos_servicios AS ts
            ON t.tg_id_servicio = ts.ts_id

            ORDER BY t.tg_fecha, t.tg_hora;
            ");

            return $data;
        } catch (\Exception $e) {
            $this->logController->saveLog(
                'Controlador: TurnosGymControllers, Función: getHorarioGym()',
                'Error al consultar horarios para asistir al gym: ' . $e->getMessage()
            );
            return response()->json(['message' => 'Error al consultar horarios para asistir al gym: ' . $e->getMessage()], 500);
        }
    }



    public function generarTurnoGym(Request $request)
    {
        $fechaSeleccionada = $request->fecha;
        $servicioId        = $request->servicio_id;

        $result = DB::select(
            'SELECT * FROM db_train_revive.generar_turnos_disponibles($1,$2)',
            [$fechaSeleccionada, $servicioId]
        );

        // Si $result está vacío, puedes lanzar la alerta
        if (empty($result)) {
            return response()->json([
                'alert' => 'No hay turnos disponibles en la fecha seleccionada.'
            ], 200);
        }

        // De lo contrario, envías los datos a la vista / frontend
        return response()->json(['slots' => $result], 200);
    }


    public function guardarTurnoGymId(Request $request)
    {
        if ($request->missing('id_turno')) {
            // 1a. El campo NO llegó
            return response()->json([
                'success' => false,
                'message' => 'El campo id_turno no está presente en la petición.',
            ], 400);
        }

        if ($request->filled('id_turno') === false) {
            return response()->json([
                'success' => false,
                'message' => 'El campo id_turno está vacío.',
            ], 400);
        }

        $idTurno = $request->input('id_turno');
        $idTipoServicio = $request->input('id_tipo_servicio');

        try {
            DB::update(
                'UPDATE db_train_revive.cpu_turnos_gym
                 SET tg_id_estado = ?
                 WHERE tg_id = ?',
                [2, $idTurno]
            );

            $descripcionAuditoria = sprintf(
                'Se registró el horario del tipo de servicio (ID: %d) el: %s con turno ID: %d',
                $idTipoServicio,
                Carbon::now()->toDateTimeString(),
                $idTurno
            );

            $this->auditoriaController->auditar(
                'cpu_turnos_gym',
                'guardarConfirmacionTurno',
                '',
                $request->all(),
                'UPDATE',
                $descripcionAuditoria
            );

            Log::info('Turno confirmado', [
                'tg_id'          => $idTurno,
                'ts_id'          => $idTipoServicio,
                'user_id'        => auth()->id() ?? null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Turno confirmado correctamente.',
                'data'    => [
                    'id_turno'        => $idTurno,
                    'estado_actual'   => 2,
                    'id_tipo_servicio' => $idTipoServicio,
                ],
            ], 200);
        } catch (\Exception $e) {
            $errorData = [
                'tg_id'      => $idTurno,
                'ts_id'      => $idTipoServicio,
                'error'      => $e->getMessage(),
                'trace'      => $e->getTraceAsString(),
            ];
            Log::error('Error al confirmar turno', $errorData);
            $this->logController->saveLog(
                'Nombre de Controlador: TurnosController, Nombre de Función: guardarConfirmacionTurno(Request $request)',
                'Error al confirmar turno',
                $errorData
            );
            return response()->json([
                'success' => false,
                'message' => 'No se pudo confirmar el turno. Intente más tarde ',
                $e->getMessage()
            ], 500);
        }
    }

    public function getGenerarTurnoGym(Request $request)
    {
        try {
            $fecha = $request->query('fecha', date('Y-m-d'));
            $servicio_id = $request->query('servicio_id', 1);

            $data = DB::select(
                'SELECT * FROM db_train_revive.generar_turnos_disponibles(?, ?)',
                [$fecha, $servicio_id]
            );

            return response()->json($data);
        } catch (\Exception $e) {
            $this->logController->saveLog(
                'Controlador: TurnosGymControllers, Función: getGenerarTurnoGym(Request $request)',
                'Error al consultar horarios para asistir al gym: ' . $e->getMessage()
            );
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function reservarTurno(Request $request)
    {
        try {
            $usuario = $request->input('p_usuario_id');
            $servicio = $request->input('p_servicio_id');
            $horario = $request->input('p_horario_gym_id');
            $fecha = $request->input('p_fecha');
            $hora = $request->input('p_hora');

            $resultado = DB::select("
                SELECT * FROM db_train_revive.reservar_turno_gym(
                    p_usuario_id := ?,
                    p_servicio_id := ?,
                    p_horario_gym_id := ?,
                    p_fecha := ?::date,
                    p_hora := ?::time
                )
            ", [$usuario, $servicio, $horario, $fecha, $hora]);

            return response()->json([
                'success' => true,
                'data' => $resultado,
            ], 200);
        } catch (\Exception $e) {
            $this->logController->saveLog(
                'Nombre de Controlador: TurnosGymControllers.php, Nombre de Función: reservarTurno(Request $request)',
                'Error al reservar el turno: ' . $e->getMessage()
            );
            return response()->json([
                'success' => false,
                'message' => 'Error al reservar turno.' . $e->getMessage(),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getTurnoGymUsuarioId($id_usuario)
    {
        try {
            $sql = "
            SELECT
                t.tg_id,
                t.tg_fecha,
                t.tg_hora,
                TO_CHAR(t.tg_hora, 'HH24:MI') AS tg_hora_2,
                t.tg_id_servicio,
                t.tg_id_estado,
                t.tg_id_user,
                t.tg_created_at,
                h.tg_hora_apertura,
                h.tg_hora_cierre,
                h.tg_capacidad_maxima,
                h.tg_tiempo_turno,
                e.estado   AS estado_nombre,
                ts.ts_descripcion,
                ts.ts_nombre
            FROM db_train_revive.cpu_turnos_gym      AS t
            JOIN db_train_revive.cpu_horarios_gym    AS h
                ON t.tg_id_horario_gym = h.tg_id
            LEFT JOIN public.users  AS u
                ON t.tg_id_user = u.id
            LEFT JOIN public.cpu_estados AS e
                ON t.tg_id_estado = e.id
            LEFT JOIN db_train_revive.cpu_tipos_servicios AS ts
            ON t.tg_id_servicio = ts.ts_id
            WHERE t.tg_id_user = :id_usuario    
            ORDER BY t.tg_fecha, t.tg_hora;
        ";

            $data = DB::select($sql, ['id_usuario' => $id_usuario]);

            return $data;
        } catch (\Exception $e) {
            $this->logController->saveLog(
                'Controlador: TurnosGymControllers, Función: getTurnoGymUsuarioId()',
                'Error al consultar horarios para asistir al gym: ' . $e->getMessage()
            );
            return response()->json(
                ['message' => 'Error al consultar horarios para asistir al gym: ' . $e->getMessage()],
                500
            );
        }
    }

    public function getEstadisticaHome()
    {
        try {
            $resultado = DB::select(
                "SELECT * from db_train_revive.obtener_estadisticas_turnos_gym() AS data;"
            );

            $json = $resultado[0]->data;

            $array = json_decode($json, true);

            return response()->json($array);
        } catch (\Exception $e) {
            $this->logController->saveLog(
                'Nombre de Controlador: TurnosGymControllers, Nombre de Función: getEstadisticaHome()',
                'Error al listar Servicio: ' . $e->getMessage()
            );

            return response()->json([
                'error' => $e->getMessage()
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
