<?php

// app/Http/Controllers/AuthController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\CpuLegalizacionMatricula;
use App\Models\CpuMatriculaConfiguracion;
use App\Models\CpuSede;
use App\Models\CpuFacultad;
use App\Models\CpuCarrera;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    
    public function login(Request $request)
{
    $credentials = $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);

    if (!Auth::attempt($credentials)) {
        // Devolver un mensaje de error cuando las credenciales son incorrectas
        return response()->json(['message' => 'Las credenciales proporcionadas son incorrectas.'], 401);
    }

    $user = Auth::user();

    if ($user->usr_estado != 8) {
        // Devolver un mensaje de error cuando el usuario no está activo
        return response()->json(['message' => 'El usuario no está activo.'], 401);
    }

    $token = $user->createToken('auth_token')->plainTextToken;
    $user->load('tipoUsuario', 'sede', 'profesion'); // Cargar las relaciones

    $userData = [
        'id' => $user->id,
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

public function loginApp(Request $request)
{
    $credentials = $request->validate([
        'email' => 'required|email',
        'cedula' => 'required',
    ]);

    // Obtener el id del periodo activo
    $id_periodo_activo = CpuMatriculaConfiguracion::where('id_estado', 8)
        ->pluck('id_periodo')
        ->first();

    if (!$id_periodo_activo) {
        // Devolver un mensaje de error cuando no hay un periodo activo
        return response()->json(['message' => 'No hay un periodo de matrícula activo.'], 401);
    }

    // Verificar si el periodo está habilitado para login
    $periodoHabilitado = CpuMatriculaConfiguracion::where('id_periodo', $id_periodo_activo)
        ->where('fecha_inicio_habil_login', '<=', now())
        ->where('fecha_fin_habil_login', '>=', now())
        ->exists();

    if (!$periodoHabilitado) {
        // Devolver un mensaje de error cuando el periodo no está habilitado para login
        return response()->json(['message' => 'El periodo no está habilitado para login.'], 401);
    }

    // Buscar el registro en la tabla cpu_legalizacion_matricula
    $ciudadano = CpuLegalizacionMatricula::where('id_periodo', $id_periodo_activo)
        ->where('email', $credentials['email'])
        ->where('cedula', $credentials['cedula'])
        ->first();

    if (!$ciudadano) {
        // Devolver un mensaje de error cuando las credenciales son incorrectas
        return response()->json(['message' => 'Las credenciales proporcionadas son incorrectas.'], 401);
    }

    // Resto del código para obtener el token y los datos del usuario
    $token = $ciudadano->createToken('auth_token')->plainTextToken;

    $userData = [
        'id' => $ciudadano->id,
        'token' => $token,
        'id_periodo' => $ciudadano->id_periodo,
        'email' => $ciudadano->email,
        'cedula' => $ciudadano->cedula,
        'apellidos' => $ciudadano->apellidos,
        'nombres' => $ciudadano->nombres,
        'genero' => $ciudadano->genero,
        'etnia' => $ciudadano->etnia,
        'discapacidad' => $ciudadano->discapacidad,
        'segmento_persona' => $ciudadano->segmento_persona,
        'nota_postulacion' => $ciudadano->nota_postulacion,
        'fecha_nacimiento' => $ciudadano->fecha_nacimiento,
        'nacionalidad' => $ciudadano->nacionalidad,
        'provincia_reside' => $ciudadano->provincia_reside,
        'canton_reside' => $ciudadano->canton_reside,
        'parroquia_reside' => $ciudadano->parroquia_reside,
        'instancia_postulacion' => $ciudadano->instancia_postulacion,
        'instancia_de_asignacion' => $ciudadano->instancia_de_asignacion,
        'gratuidad' => $ciudadano->gratuidad,
        'observacion_gratuidad' => $ciudadano->observacion_gratuidad,
        'copia_identificacion' =>url('Files/' .  $ciudadano->copia_identificacion),
        'copia_titulo_acta_grado' =>url('Files/' .  $ciudadano->copia_titulo_acta_grado),
        'copia_aceptacion_cupo' =>url('Files/' .  $ciudadano->copia_aceptacion_cupo),
        'listo_para_revision' => $ciudadano->listo_para_revision,
        'legalizo_matricula' => $ciudadano->legalizo_matricula,
    ];

    $sede = CpuSede::find($ciudadano->id_sede);
    if ($sede) {
        $userData['sede'] = $sede->nombre_sede;
    }

    // Obtener el nombre de la facultad
    $facultadNombre = null;
    if ($ciudadano->id_facultad) {
        $facultad = CpuFacultad::find($ciudadano->id_facultad);
        if ($facultad) {
            $facultadNombre = $facultad->fac_nombre;
        }
    }
    $userData['facultad'] = $facultadNombre;

    // Obtener el nombre de la carrera
    $carreraNombre = null;
    if ($ciudadano->id_carrera) {
        $carrera = CpuCarrera::find($ciudadano->id_carrera);
        if ($carrera) {
            $carreraNombre = $carrera->name;
        }
    }
    $userData['carrera'] = $carreraNombre;

    return response()->json($userData);
}


    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json(['message' => 'Desconectado/a']);
    }

    public function me(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }

        $userData = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'usr_tipo' => $user->tipoUsuario ? $user->tipoUsuario->role : null,
        ];

        // Desencriptar el campo password
        // $userData['password'] = Hash::make($user->password);
        $userData['password'] = $user->password;

        return response()->json($userData);
    }
}

