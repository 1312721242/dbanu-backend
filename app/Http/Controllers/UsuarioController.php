<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Models\CpuProfesion;
use App\Models\CpuSede;


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
            // Validación
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email,'.$id,
                'usr_sede' => 'nullable|integer',
                'usr_facultad' => 'nullable|integer',
                'usr_carrera' => 'nullable|integer',
                'usr_profesion' => 'nullable|integer',
                'api_token' => 'nullable|string|max:60',
            ]);

            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()], 400);
            }

            // Buscar el usuario por ID
            $usuario = User::find($id);

            if (!$usuario) {
                return response()->json(['error' => 'Usuario no encontrado'], 404);
            }

            // Solo actualiza los campos que están presentes en la solicitud
            $updateData = array_filter($request->only([
                'name',
                'email',
                'usr_sede',
                'usr_facultad',
                'usr_carrera',
                'usr_profesion',
                'api_token',
            ]));

            $usuario->update($updateData);

            return response()->json(['success' => true, 'message' => 'Información personal actualizada correctamente']);
        }


        public function search(Request $request)
            {
                // Obtener el término de búsqueda desde la solicitud
                $searchTerm = $request->input('bus');

                // Realizar la consulta en la tabla de usuarios buscando por nombre
                // y seleccionando todos los campos necesarios
                $users = User::where('name', 'ILIKE', "%$searchTerm%")
                    ->get([
                        'id', 'name','email', 'usr_tipo', 'usr_estado',
                        'usr_sede', 'usr_facultad', 'usr_carrera',
                        'usr_profesion', 'api_token'
                    ]);

                // Devolver los resultados como respuesta JSON
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
                $users = User::with(['tipoUsuario', 'profesion','sede'])
                            ->where('usr_tipo', $usr_tipo)
                            ->where('usr_estado', 8)
                            ->get();
                    // Mapear para incluir nombre y dirección de la sede
                    // $users = $users->map(function ($user) {
                    //     return [
                    //         'id' => $user->id,
                    //         'name' => $user->name,
                    //         'email' => $user->email,
                    //         'usr_tipo' => $user->usr_tipo,
                    //         'tipo_usuario' => $user->tipoUsuario->name ?? null,
                    //         'profesion' => $user->profesion->name ?? null,
                    //         'sede_nombre' => $user->sede->nombre_sede ?? null,  // Nombre de la sede
                    //         'sede_direccion' => $user->sede->direccion_sede ?? null,  // Dirección de la sede
                    //     ];
                    // });
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
        /////CAMBIO DE CONTRASEÑA
        public function cambiarContrasena(Request $request)
{
    // Convertir `id` a número y validar
    $request->merge(['id' => (int) $request->id]);

    $validator = Validator::make($request->all(), [
        'id' => 'required|exists:users,id',
        'password_actual' => 'required',
        'nueva_contrasena' => 'nullable|min:8|confirmed', // Se mantiene la validación de `confirmed`
        'foto_perfil' => 'nullable|image|mimes:jpeg,png,jpg|max:2048' // Asegurar que la imagen sea válida
    ], [
        'password_actual.required' => 'La contraseña actual es obligatoria.',
        'nueva_contrasena.required' => 'La nueva contraseña es obligatoria.',
        'nueva_contrasena.min' => 'La nueva contraseña debe tener al menos 8 caracteres.',
        'nueva_contrasena.confirmed' => 'Las contraseñas nuevas no coinciden.',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'message' => $validator->errors()
        ], 400);
    }

    // Obtener usuario
    $user = User::find($request->id);

    // Validar contraseña actual
    if (!Hash::check($request->password_actual, $user->password)) {
        return response()->json([
            'success' => false,
            'message' => ['password_actual' => 'La contraseña actual es incorrecta.']
        ], 400);
    }

    // Actualizar la contraseña si se proporciona
    if ($request->filled('nueva_contrasena')) {
        $user->password = Hash::make($request->nueva_contrasena);
    }

    // Guardar nueva imagen si se sube
    if ($request->hasFile('foto_perfil')) {
        $imagen = $request->file('foto_perfil');
        $nombreImagen = time() . '.' . $imagen->getClientOriginalExtension();
        $imagen->move(public_path('Perfiles'), $nombreImagen);

        // ✅ Guardar solo el nombre del archivo en la base de datos
        $user->foto_perfil = $nombreImagen;
    }

    $user->save();

    return response()->json([
        'success' => true,
        'message' => 'Contraseña y foto de perfil actualizadas correctamente.',
        'foto_perfil' => url('Perfiles/' . $user->foto_perfil) // ✅ Devolver la URL completa para el frontend
    ]);
}


}
