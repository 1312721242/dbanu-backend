<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CpuNotificacionMatricula;
use Illuminate\Support\Facades\Auth;

class CpuNotificacionMatriculaController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum'); // Mantener el middleware 'auth:sanctum' para proteger las rutas
    }

    public function index(Request $request)
    {
        // Obtener el usuario autenticado desde la solicitud
        $user = $request->user();

        // Obtener todas las notificaciones del usuario logueado
        $notificaciones = CpuNotificacionMatricula::where('id_legalizacion', $user->id)->get();

        return response()->json($notificaciones);
    }

    public function markAsRead(Request $request, $id)
    {
        // Obtener el usuario autenticado desde la solicitud
        $user = $request->user();

        // Obtener la notificación por su ID
        $notificacion = CpuNotificacionMatricula::where('id', $id)
            ->where('id_legalizacion', $user->id)
            ->first();

        if (!$notificacion) {
            return response()->json(['message' => 'Notificación no encontrada'], 404);
        }

        // Marcar la notificación como leída (estado 17)
        $notificacion->id_estado = 17;
        $notificacion->save();

        return response()->json(['message' => 'Notificación marcada como leída']);
    }

    public function unreadCount(Request $request)
    {
        // Obtener el usuario autenticado desde la solicitud
        $user = $request->user();

        // Contar todas las notificaciones no leídas (estado 16) del usuario logueado
        $count = CpuNotificacionMatricula::where('id_legalizacion', $user->id)->where('id_estado', 16)->count();

        return response()->json(['noti_no_leidas' => $count]);
    }

}
