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
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;

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
            return response()->json(['message' => 'El usuario no está activo.'], 200);
        }

        $token = $user->createToken('auth_token')->plainTextToken;
        $user->load('tipoUsuario', 'sede', 'profesion'); // Cargar las relaciones

        $userData = [
            'id' => $user->id,
            'token' => $token,
            'name' => $user->name,
            'usr_tipo' => $user->tipoUsuario->role,
            'foto_perfil' => url('Perfiles/' . $user->foto_perfil), // Generar URL completa
        ];

        if ($user->sede) {
            $userData['usr_sede'] = $user->sede->nombre_sede;
        }

        if ($user->profesion) {
            $userData['usr_profesion'] = $user->profesion->profesion;
        }

        // Auditoría
        $this->auditar('auth', 'login', '', $user->email, 'LOGIN', "LOGIN DE USUARIO: {$user->email}", $request);

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
            'copia_identificacion' => url('Files/' .  $ciudadano->copia_identificacion),
            'copia_titulo_acta_grado' => url('Files/' .  $ciudadano->copia_titulo_acta_grado),
            'copia_aceptacion_cupo' => url('Files/' .  $ciudadano->copia_aceptacion_cupo),
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

        // Auditoría
        $this->auditar('auth', 'loginApp', '', $ciudadano->email, 'LOGIN', "LOGIN DE USUARIO APP: {$ciudadano->email}", $request);

        return response()->json($userData);
    }


    public function logout(Request $request)
    {
        $user = $request->user();
        $user->tokens()->delete();

        // Eliminar el token de restablecimiento de contraseña de la base de datos
        DB::table('password_reset_tokens')->where('email', $user->email)->delete();

        // Auditoría
        $this->auditar('auth', 'logout', $user->email, '', 'LOGOUT', "LOGOUT DE USUARIO: {$user->email}", $request);

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

        // Auditoría
        $this->auditar('auth', 'me', '', $user->email, 'CONSULTA', "CONSULTA DE USUARIO: {$user->email}", $request);

        return response()->json($userData);
    }

    private function auditar($tabla, $campo, $dataOld, $dataNew, $tipo, $descripcion, $request = null)
    {
        $usuario = $request ? $request->user()->name : auth()->user()->name;
        $ip = $request ? $request->ip() : request()->ip();
        $ipv4 = gethostbyname(gethostname());
        $publicIp = file_get_contents('http://ipecho.net/plain');
        $ioConcatenadas = 'IP LOCAL: ' . $ip . '  --IPV4: ' . $ipv4 . '  --IP PUBLICA: ' . $publicIp;
        $nombreequipo = gethostbyaddr($ip);
        $userAgent = $request ? $request->header('User-Agent') : request()->header('User-Agent');
        $tipoEquipo = 'Desconocido';

        if (stripos($userAgent, 'Mobile') !== false) {
            $tipoEquipo = 'Celular';
        } elseif (stripos($userAgent, 'Tablet') !== false) {
            $tipoEquipo = 'Tablet';
        } elseif (stripos($userAgent, 'Laptop') !== false || stripos($userAgent, 'Macintosh') !== false) {
            $tipoEquipo = 'Laptop';
        } elseif (stripos($userAgent, 'Windows') !== false || stripos($userAgent, 'Linux') !== false) {
            $tipoEquipo = 'Computador de Escritorio';
        }
        $nombreUsuarioEquipo = get_current_user() . ' en ' . $tipoEquipo;

        $fecha = now();
        $codigo_auditoria = strtoupper($tabla . '_' . $campo . '_' . $tipo );
        DB::table('cpu_auditoria')->insert([
            'aud_user' => $usuario,
            'aud_tabla' => $tabla,
            'aud_campo' => $campo,
            'aud_dataold' => $dataOld,
            'aud_datanew' => $dataNew,
            'aud_tipo' => $tipo,
            'aud_fecha' => $fecha,
            'aud_ip' => $ioConcatenadas,
            'aud_tipoauditoria' => $this->getTipoAuditoria($tipo),
            'aud_descripcion' => $descripcion,
            'aud_nombreequipo' => $nombreequipo,
            'aud_descrequipo' => $nombreUsuarioEquipo,
            'aud_codigo' => $codigo_auditoria,
            'created_at' => now(),
            'updated_at' => now(),

        ]);
    }

    private function getTipoAuditoria($tipo)
    {
        switch ($tipo) {
            case 'CONSULTA':
                return 1;
            case 'INSERCION':
                return 3;
            case 'MODIFICACION':
                return 2;
            case 'ELIMINACION':
                return 4;
            case 'LOGIN':
                return 5;
            case 'LOGOUT':
                return 6;
            default:
                return 0;
        }
    }
}
