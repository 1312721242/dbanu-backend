<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Models\CpuProfesion;


class UsuarioController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }
    public function agregarUsuario(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:6',
            'usr_tipo' => 'required|exists:cpu_userrole,id_userrole',
            'usr_sede' => 'required|exists:cpu_sede,id',
            'usr_facultad' => 'sometimes|exists:cpu_facultad,id',
            'usr_carrera' => 'sometimes|exists:cpu_carrera,id',
            'usr_profesion' => 'required|exists:cpu_profesion,id',
            'api_token' => 'required|string|max:10',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $usuario = User::create([
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'password' => Hash::make($request->input('password')),
            'usr_tipo' => $request->input('usr_tipo'),
            'usr_sede' => $request->input('usr_sede'),
            'usr_facultad' => $request->input('usr_facultad'),
            'usr_carrera' => $request->input('usr_carrera'),
            'usr_profesion' => $request->input('usr_profesion'),
            'api_token' => $request->input('api_token'),
        ]);

        return response()->json(['success' => true, 'message' => 'Usuario agregado correctamente', 'user' => ['id' => $usuario->id]]);
    }



        public function darDeBajaUsuario(Request $request, $id)
        {
            $usuario = User::find($id);

            if (!$usuario) {
                return response()->json(['error' => 'Usuario no encontrado'], 404);
            }

            $usuario->update(['usr_estado' => 9]);

            return response()->json(['success' => true, 'message' => 'Usuario dado de baja correctamente']);
        }


        public function darDeAltaUsuario(Request $request, $id)
        {
            $usuario = User::find($id);

            if (!$usuario) {
                return response()->json(['error' => 'Usuario no encontrado'], 404);
            }

            $usuario->update(['usr_estado' => 8]);

            return response()->json(['success' => true, 'message' => 'Usuario dado de alta correctamente']);
        }


        public function cambiarPassword(Request $request, $id)
        {
            $validator = Validator::make($request->all(), [
                'password' => 'required|string|min:6',
            ]);

            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()], 400);
            }

            $usuario = User::find($id);

            if (!$usuario) {
                return response()->json(['error' => 'Usuario no encontrado'], 404);
            }

            $usuario->update(['password' => Hash::make($request->input('password'))]);

            return response()->json(['success' => true, 'message' => 'Contraseña cambiada correctamente']);
        }

        public function cambiarPasswordApp(Request $request)
        {
            $validator = Validator::make($request->all(), [
                'current_password' => 'required|string|min:6',
                'new_password' => 'required|string|min:6',
            ]);
        
            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()], 400);
            }
        
            $usuario = Auth::user();
        
            if (!$usuario) {
                return response()->json(['error' => 'Usuario no encontrado'], 404);
            }
        
            // Validar la contraseña actual
            if (!Hash::check($request->input('current_password'), $usuario->password)) {
                return response()->json(['error' => 'La contraseña actual es incorrecta'], 400);
            }
        
            // Cambiar la contraseña
            $usuario->update(['password' => Hash::make($request->input('new_password'))]);
        
            return response()->json(['success' => true, 'message' => 'Contraseña cambiada correctamente']);
        }
        
        



        public function actualizarInformacionPersonal(Request $request, $id)
        {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email,'.$id,
                'usr_sede' => 'nullable|integer',
                'usr_facultad' => 'nullable|integer',
                'usr_carrera' => 'nullable|integer',
                'usr_profesion' => 'nullable|integer',
            ]);

            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()], 400);
            }

            $usuario = User::find($id);

            if (!$usuario) {
                return response()->json(['error' => 'Usuario no encontrado'], 404);
            }

            $usuario->update([
                'name' => $request->input('name'),
                'email' => $request->input('email'),
                'usr_sede' => $request->input('usr_sede'),
                'usr_facultad' => $request->input('usr_facultad'),
                'usr_carrera' => $request->input('usr_carrera'),
                'usr_profesion' => $request->input('usr_profesion'),
            ]);

            return response()->json(['success' => true, 'message' => 'Información personal actualizada correctamente']);
        }

        public function search(Request $request)
            {
                $searchTerm = $request->input('bus');

                $users = User::where('name', 'ILIKE', "%$searchTerm%")->get(['name']);

                return response()->json($users);
            }
        
            public function buscarfuncionariorol(Request $request)
            {
                $validator = Validator::make($request->all(), [
                    'usr_tipo' => 'required|integer|exists:users,usr_tipo',
                ]);
            
                if ($validator->fails()) {
                    return response()->json(['error' => $validator->errors()], 400);
                }
            
                $usr_tipo = $request->input('usr_tipo');
                $users = User::where('usr_tipo', $usr_tipo)
                                ->where('usr_estado', 8)
                                ->get();
            
                return response()->json($users);
            }

        public function obtenerInformacion($id)
        {
            try {
                $funcionario = User::find($id);
                if (!$funcionario) {
                    \Log::error("Funcionario no encontrado con ID: $id");
                    return response()->json(['error' => 'Funcionario no encontrado'], 404);
                }

                $profesion = CpuProfesion::find($funcionario->usr_profesion);
                if (!$profesion) {
                    \Log::error("Profesion no encontrada para ID: " . $funcionario->usr_profesion);
                }

                return response()->json([
                    'name' => $funcionario->name,
                    'profesion' => $profesion ? $profesion->profesion : null,
                ]);
            } catch (\Exception $e) {
                \Log::error('Error al obtener información del funcionario: ' . $e->getMessage());
                return response()->json(['error' => 'Error interno del servidor'], 500);
            }
        }
            
            
}
