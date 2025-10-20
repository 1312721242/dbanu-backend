<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Support\Facades\Log;
use App\Models\UserSbe;

class TokenController extends Controller
{
    /**
     * 🔁 Refrescar token de acceso con Sanctum
     *
     * Requiere: Header Authorization: Bearer <refresh_or_access_token_actual>
     * Devuelve: nuevo access_token y nuevo refresh_token
     */
    public function refresh(Request $request)
    {
        $incomingToken = $request->bearerToken();

        if (!$incomingToken) {
            return response()->json(['message' => 'Token no enviado'], 401);
        }

        // Buscar el token actual en la base de datos
        $tokenModel = PersonalAccessToken::findToken($incomingToken);

        if (!$tokenModel) {
            return response()->json(['message' => 'Token inválido'], 401);
        }

        // Obtener el usuario dueño del token
        $user = $tokenModel->tokenable;

        if (!$user || !$user instanceof UserSbe) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }

        // 🧹 Eliminar todos los tokens anteriores del usuario
        $deleted = $user->tokens()->delete();
        Log::info('🧹 Tokens antiguos eliminados', [
            'user_id' => $user->id,
            'cantidad_eliminada' => $deleted,
        ]);

        // 🔐 Crear nuevos tokens Sanctum
        $newAccessToken  = $user->createToken('gym-access')->plainTextToken;
        $newRefreshToken = $user->createToken('gym-refresh')->plainTextToken;

        Log::info('♻️ Tokens renovados correctamente', [
            'user_id' => $user->id,
            'email'   => $user->email,
        ]);

        return response()->json([
            'access_token'  => $newAccessToken,
            'refresh_token' => $newRefreshToken,
            'token_type'    => 'Bearer',
            'user_id'       => $user->id,
        ], 200);
    }

    /**
     * 🚪 Cerrar sesión (invalida todos los tokens del usuario)
     */
    public function logout(Request $request)
    {
        $user = $request->user();

        if ($user) {
            $deleted = $user->tokens()->delete();
            Log::info('🚪 Sesión cerrada, tokens eliminados', [
                'user_id' => $user->id,
                'cantidad_eliminada' => $deleted,
            ]);
        }

        return response()->json(['message' => 'Sesión cerrada correctamente']);
    }
}
