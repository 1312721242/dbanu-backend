<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class GymServiciosController extends Controller
{
    /**
     * 🧩 Todas las rutas de este controlador deben usar auth.flex
     * para validar tokens de usuarios App\Models\UserSbe
     */

    // ==============================================================
    // 📦 CATEGORÍAS Y SERVICIOS
    // ==============================================================

    /**
     * 🏋️‍♀️ Obtiene todas las categorías de servicios
     */
    public function getCategoriaServicio()
    {
        try {
            $data = DB::select("SELECT * FROM db_train_revive.cpu_categoria_servicios ORDER BY cat_id");

            Log::info('✅ Categorías de servicio cargadas', ['count' => count($data)]);
            return response()->json($data, 200);
        } catch (\Throwable $e) {
            Log::error('❌ Error al listar categorías: ' . $e->getMessage());
            return response()->json(['error' => 'Error al listar categorías', 'detalle' => $e->getMessage()], 500);
        }
    }

    /**
     * 🎯 Obtiene los servicios de una categoría específica
     */
    public function getServicioCategoriaId($idCategoria)
    {
        try {
            $data = DB::select(
                "SELECT * FROM db_train_revive.cpu_tipos_servicios WHERE ts_id_categoria = ? ORDER BY ts_id",
                [$idCategoria]
            );

            Log::info('✅ Servicios cargados por categoría', [
                'id_categoria' => $idCategoria,
                'count' => count($data)
            ]);

            return response()->json($data, 200);
        } catch (\Throwable $e) {
            Log::error('❌ Error al listar servicios por categoría: ' . $e->getMessage());
            return response()->json(['error' => 'Error al listar servicios', 'detalle' => $e->getMessage()], 500);
        }
    }

    // ==============================================================
    // 🕒 TURNOS Y RESERVAS
    // ==============================================================

    /**
     * 📅 Obtiene todos los turnos disponibles (función generar_turnos_disponibles)
     */
    public function generarTurnoGym(Request $request)
{
    $fechaSeleccionada = $request->fecha;
    $servicioId        = $request->servicio_id;
    $tipoUsuario       = $request->tipo_usuario ?? 1; // 👈 llega desde el frontend
    $estadosPermitidos = [1];

    try {
        $result = DB::select(
            'SELECT * FROM db_train_revive.generar_turnos_disponibles(?, ?, ?, ?)',
            [$fechaSeleccionada, $servicioId, $tipoUsuario, $estadosPermitidos]
        );

        if (empty($result)) {
            return response()->json([
                'alert' => 'No hay turnos disponibles en la fecha seleccionada.'
            ], 200);
        }

        return response()->json(['slots' => $result], 200);
    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Error al generar los turnos.',
            'detalle' => $e->getMessage(),
        ], 500);
    }
}



    /**
     * 🏷️ Guarda (reserva) un turno para un usuario
     */
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

            Log::info('✅ Turno reservado correctamente', [
                'usuario' => $usuario,
                'servicio' => $servicio,
                'horario' => $horario,
                'fecha' => $fecha,
                'hora' => $hora
            ]);

            return response()->json(['success' => true, 'data' => $resultado], 200);
        } catch (\Throwable $e) {
            Log::error('❌ Error al reservar turno: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al reservar turno.',
                'detalle' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 📊 Estadísticas del Home del Gym
     */
    public function getEstadisticaHome()
    {
        try {
            $resultado = DB::select("SELECT * from db_train_revive.obtener_estadisticas_turnos_gym() AS data;");
            $json = $resultado[0]->data ?? '{}';
            $array = json_decode($json, true);

            Log::info('📈 Estadísticas home obtenidas', ['count' => count($array ?? [])]);

            return response()->json($array ?? [], 200);
        } catch (\Throwable $e) {
            Log::error('❌ Error al obtener estadísticas: ' . $e->getMessage());
            return response()->json(['error' => 'Error al obtener estadísticas', 'detalle' => $e->getMessage()], 500);
        }
    }

    /**
     * 👤 Turnos de un usuario específico
     */
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
                h.tg_hora_apertura,
                h.tg_hora_cierre,
                h.tg_capacidad_maxima,
                h.tg_tiempo_turno,
                e.estado AS estado_nombre,
                ts.ts_descripcion,
                ts.ts_nombre
            FROM db_train_revive.cpu_turnos_gym AS t
            JOIN db_train_revive.cpu_horarios_gym AS h ON t.tg_id_horario_gym = h.tg_id
            LEFT JOIN public.cpu_estados AS e ON t.tg_id_estado = e.id
            LEFT JOIN db_train_revive.cpu_tipos_servicios AS ts ON t.tg_id_servicio = ts.ts_id
            WHERE t.tg_id_user = ?
            ORDER BY t.tg_fecha, t.tg_hora;
            ";

            $data = DB::select($sql, [$id_usuario]);

            Log::info('✅ Turnos por usuario cargados', ['user_id' => $id_usuario, 'count' => count($data)]);
            return response()->json($data, 200);
        } catch (\Throwable $e) {
            Log::error('❌ Error al consultar turnos del usuario: ' . $e->getMessage());
            return response()->json(['error' => 'Error al consultar turnos del usuario', 'detalle' => $e->getMessage()], 500);
        }
    }
}
