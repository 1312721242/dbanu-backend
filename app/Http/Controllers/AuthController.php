<?php

// app/Http/Controllers/AuthController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
{
    $credentials = $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);

    if (Auth::attempt($credentials)) {
        $user = User::where('email', $request->email)->firstOrFail();

        if ($user->usr_estado != 1) {
            throw ValidationException::withMessages([
                'email' => ['El usuario no está activo.'],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;
        $user->load('tipoUsuario', 'sede', 'profesion'); // Cargar las relaciones

        $userData = [
            'token' => $token,
            'name' => $user->name,
            'usr_tipo' => $user->tipoUsuario->role,
        ];

        if ($user->sede) {
            $userData['usr_sede'] = $user->sede->nombre_sede;
        }

        if ($user->profesion) {
            $userData['usr_profesion'] = $user->profesion->profesion;
        }

        return response()->json($userData);
    }

    throw ValidationException::withMessages([
        'email' => ['Las credenciales proporcionadas son incorrectas.'],
    ]);
}




    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json(['message' => 'Desconectado/a']);
    }
}

