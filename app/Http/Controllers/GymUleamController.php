<?php

namespace App\Http\Controllers;

use App\Models\UserSbe;
use App\Models\CpuPersona;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;

class GymUleamController extends Controller
{
    /**
     * 🔐 Login general: estudiante o personal
     */
    public function login(Request $request)
    {
        $request->validate([
            'cedula' => 'required|string|max:20',
            'tipo'   => 'required|in:estudiante,personal',
        ]);

        return $request->tipo === 'estudiante'
            ? $this->loginEstudiante($request->cedula)
            : $this->loginPersonal($request->cedula);
    }

    /**
     * 👨‍🎓 Login Estudiante
     */
   private function loginEstudiante(string $cedula)
{
    try {
        Log::info("🎓 LOGIN ESTUDIANTE: {$cedula}");

        // 🌍 Detectar si el entorno es local o producción
        $isLocal = app()->environment(['local', 'testing']);

        // 1️⃣ Obtener token de Azure (OAuth2 Client Credentials)
        $tokenResponse = Http::withOptions([
                'verify' => !$isLocal, // ❌ Evita error SSL solo en local
                'timeout' => 20,
            ])
            ->asForm()
            ->post(
                'https://login.microsoftonline.com/31a17900-7589-4cfc-b11a-f4e83c27b8ed/oauth2/v2.0/token',
                [
                    'grant_type' => 'client_credentials',
                    'client_id' => '13e24fa4-9c64-4653-a96c-20964510b52a',
                    'client_secret' => 'D1c8Q~gB11NpYVW7TBkTvoW1QSEHorolMBXcNcrs',
                    'scope' => 'https://service.flow.microsoft.com//.default',
                ]
            );

        if ($tokenResponse->failed()) {
            $errorBody = $tokenResponse->body();
            Log::error("❌ Error al obtener token de Azure: {$errorBody}");
            return response()->json([
                'error' => 'No se pudo obtener token de autenticación desde Azure.',
                'detalle' => $errorBody,
            ], 500);
        }

        $accessToken = $tokenResponse->json()['access_token'] ?? null;
        if (!$accessToken) {
            Log::error("⚠️ Azure no devolvió access_token para estudiante {$cedula}");
            return response()->json(['error' => 'Azure no devolvió token válido.'], 500);
        }

        // 2️⃣ Consulta a Logic App de Microsoft (datos del estudiante)
        $response = Http::withOptions([
                'verify' => !$isLocal,
                'timeout' => 25,
            ])
            ->withHeaders([
                'Authorization' => "Bearer {$accessToken}",
                'Content-Type' => 'application/json',
            ])
            ->post(
                'https://prod-146.westus.logic.azure.com:443/workflows/033f8b54b4cc42f4ac0fdea481c0c27c/triggers/manual/paths/invoke?api-version=2016-06-01',
                ['identificacion' => $cedula]
            );

        if ($response->failed()) {
            $errorBody = $response->body();
            Log::error("❌ Error al consultar Logic App (estudiante {$cedula}): {$errorBody}");
            return response()->json([
                'error' => 'Error al consultar Logic App (estudiantes)',
                'detalle' => $errorBody,
            ], 500);
        }

        $datos = $response->json();
        Log::info("✅ Datos estudiante recibidos para {$cedula}: " . json_encode($datos));

        // 3️⃣ Validar estado de matrícula
        $estadoMatricula = strtoupper(trim($datos['estadoMatricula'] ?? 'NO'));
        if ($estadoMatricula !== 'ESTÁ MATRICULADO') {
            return response()->json([
                'message' => 'Solo los estudiantes matriculados pueden acceder.',
                'estadoMatricula' => $estadoMatricula,
            ], 401);
        }

        // 4️⃣ Crear/actualizar usuario localmente y emitir tokens
        return $this->crearUsuarioEstudiante($datos, $cedula);
    } catch (\Throwable $e) {
        Log::error("❌ Error loginEstudiante ({$cedula}): " . $e->getMessage());
        return response()->json([
            'error' => 'Error interno al autenticar estudiante.',
            'detalle' => $e->getMessage(),
        ], 500);
    }
}


    /**
     * 👨‍💼 Login Personal
     */
    private function loginPersonal(string $cedula)
    {
        try {
            Log::info("🏢 LOGIN PERSONAL: {$cedula}");

            // 1️⃣ Token Azure
            $tokenResponse = Http::withOptions(['verify' => false])
                ->asForm()
                ->post(
                    'https://login.microsoftonline.com/31a17900-7589-4cfc-b11a-f4e83c27b8ed/oauth2/v2.0/token',
                    [
                        'grant_type' => 'client_credentials',
                        'client_id' => '1111b1c0-8b4f-4f50-96ea-ea4cc2df1c6d',
                        'client_secret' => 'iZH8Q~TRpKFW5PCG4OlBw-R1SDDnpT-611myKasT',
                        'scope' => 'https://service.flow.microsoft.com//.default',
                    ]
                );

            if ($tokenResponse->failed()) {
                return response()->json(['error' => 'Error al obtener token Azure (personal)'], 500);
            }

            $accessToken = $tokenResponse->json()['access_token'];

            // 2️⃣ Consulta Logic App (empleados)
            $response = Http::withOptions(['verify' => false])
                ->withHeaders([
                    'Authorization' => "Bearer {$accessToken}",
                    'Content-Type'  => 'application/json',
                ])
                ->get("https://prod-160.westus.logic.azure.com/workflows/79256a92249b4f85bc6c0737d8d17d10/triggers/manual/paths/invoke/cedula/{$cedula}?api-version=2016-06-01");

            if ($response->failed()) {
                return response()->json(['error' => 'Error al consultar Logic App (personal)'], 500);
            }

            $datos = $response->json();
            Log::info("✅ Datos personal: " . json_encode($datos));

            if (empty($datos['Cedula']) || empty($datos['Nombres'])) {
                return response()->json(['message' => 'No se encontraron datos del empleado'], 404);
            }

            return $this->crearUsuarioPersonal($datos);
        } catch (\Throwable $e) {
            Log::error("❌ Error loginPersonal: " . $e->getMessage());
            return response()->json(['error' => 'Error interno al autenticar personal'], 500);
        }
    }

    /**
     * 🧩 Crear usuario estudiante + persona + datos académicos
     */
    private function crearUsuarioEstudiante(array $datos, string $cedula)
    {
        DB::beginTransaction();
        try {
            $email  = $datos['emailInstitucional'] ?? $datos['emailPersonal'] ?? "{$cedula}@uleam.edu.ec";
            $nombre = $datos['nombres'] ?? 'Estudiante ULEAM';

            $user = UserSbe::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $nombre,
                    'password' => Hash::make($cedula),
                    'id_tipo_usuario' => 1,
                ]
            );

            $persona = CpuPersona::updateOrCreate(['cedula' => $cedula], [
                'nombres' => $nombre,
                'direccion' => $datos['direccionDomicilio'] ?? null,
                'celular' => $datos['celular'] ?? null,
                'fechanaci' => $datos['fechaNacimiento'] ?? null,
                'sexo' => $datos['sexo'] ?? null,
                'nacionalidad' => $datos['nacionalidad'] ?? null,
                'provincia' => $datos['provincia'] ?? null,
                'ciudad' => $datos['ciudad'] ?? null,
                'tipoetnia' => $datos['etnia'] ?? null,
                'discapacidad' => $datos['discapacidad'] ?? null,
                'id_tipo_usuario' => 1,
            ]);

            // 🔹 Guardar datos académicos en cpu_datos_estudiantes
            DB::table('cpu_datos_estudiantes')->updateOrInsert(
                ['id_persona' => $persona->id],
                [
                    'campus' => $datos['campus'] ?? 'SIN INFORMACIÓN',
                    'facultad' => $datos['facultad'] ?? 'SIN INFORMACIÓN',
                    'carrera' => $datos['carrera'] ?? 'SIN INFORMACIÓN',
                    'semestre_actual' => $datos['semestreActual'] ?? null,
                    'estado_estudiante' => $datos['estadoEstudiante'] ?? null,
                    'estado_civil' => $datos['estadoCivil'] ?? null,
                    'email_institucional' => $datos['emailInstitucional'] ?? null,
                    'email_personal' => $datos['emailPersonal'] ?? null,
                    'telefono' => $datos['celular'] ?? null,
                    'segmentacion_persona' => $datos['segmentacionPersona'] ?? null,
                    'periodo' => $datos['periodo'] ?? null,
                    'estado_matricula' => $datos['estadoMatricula'] ?? null,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

            DB::commit();
            return $this->emitirTokens($user, 'estudiante');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("❌ Error al crear usuario estudiante: " . $e->getMessage());
            return response()->json(['error' => 'Error al registrar estudiante'], 500);
        }
    }

    /**
     * 🧩 Crear usuario personal + persona + datos laborales
     */
    private function crearUsuarioPersonal(array $datos)
    {
        DB::beginTransaction();
        try {
            $email = $datos['CorreoInstitucional']
            ?? $datos['CorreoElectronico']
            ?? "{$datos['Cedula']}@uleam.edu.ec";

            $nombre = $datos['Nombres'] ?? 'Personal ULEAM';

            $user = UserSbe::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $nombre,
                    'password' => Hash::make($datos['Cedula']),
                    'id_tipo_usuario' => 2,
                ]
            );

            $persona = CpuPersona::updateOrCreate(['cedula' => $datos['Cedula']], [
                'nombres' => $nombre,
                'celular' => $datos['TelefonoMovil'] ?? null,
                'sexo' => $datos['Sexo'] ?? null,
                'nacionalidad' => $datos['Nacionalidad'] ?? null,
                'provincia' => $datos['ProvinciaDomicilio'] ?? null,
                'ciudad' => $datos['CantonDomicilio'] ?? null,
                'direccion' => $datos['Direccion'] ?? null,
                'id_tipo_usuario' => 2,
            ]);

            // 🔹 Guardar datos laborales en cpu_datos_empleado
            DB::table('cpu_datos_empleados')->updateOrInsert(
                ['id_persona' => $persona->id],
                [
                    'emailinstitucional' => $datos['CorreoInstitucional'] ?? $email,
                    'correopersonal' => $datos['CorreoPersonal'] ?? null,
                    'puesto' => $datos['Cargo'] ?? 'SIN INFORMACIÓN',
                    'unidad' => $datos['NombreSubProceso'] ?? null,
                    'nombreproceso' => $datos['NombreProceso'] ?? null,
                    'sector' => $datos['Sector'] ?? null,
                    'fechaingre' => $datos['FechaIngreso'] ?? null,
                    'regimen1' => $datos['Regimen'] ?? null,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

            DB::commit();
            return $this->emitirTokens($user, 'personal');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("❌ Error al crear usuario personal: " . $e->getMessage());
            return response()->json(['error' => 'Error al registrar personal'], 500);
        }
    }

    /**
     * 🔁 Emite access y refresh tokens
     */
    private function emitirTokens(UserSbe $user, string $rol)
    {
        // Elimina refresh tokens antiguos
        $user->tokens()->where('name', 'refresh_token')->delete();

        $accessToken = $user->createToken('access_token')->plainTextToken;
        $refreshToken = $user->createToken('refresh_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'rol' => $rol,
            'token' => $accessToken,
            'refresh_token' => $refreshToken,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'tipo_usuario' => $user->id_tipo_usuario,
            ],
            'issued_at' => now(),
        ]);
    }

    /**
 * 👤 Devuelve la información completa del usuario autenticado
 */
public function userInfo(Request $request)
{
    try {
        $user = $request->user(); // ✅ token Sanctum válido
        if (!$user) {
            return response()->json(['error' => 'Usuario no autenticado'], 401);
        }

        // 🔍 Buscar persona asociada (por tipo de usuario y nombre similar)
        $persona = DB::table('cpu_personas')
            ->where('id_tipo_usuario', $user->id_tipo_usuario)
            ->whereRaw("unaccent(lower(nombres)) LIKE unaccent(lower(?))", ["%{$user->name}%"])
            ->orderByDesc('id')
            ->first();

        $datosExtra = null;

        // 🔹 Datos extendidos según tipo de usuario
        if ($user->id_tipo_usuario == 1) {
            $datosExtra = DB::table('cpu_datos_estudiantes')
                ->where('id_persona', $persona->id ?? null)
                ->first();
        } elseif ($user->id_tipo_usuario == 2) {
            $datosExtra = DB::table('cpu_datos_empleados')
                ->where('id_persona', $persona->id ?? null)
                ->first();
        }

        // ✅ Estructura limpia de respuesta
        return response()->json([
            'success' => true,
            'id' => $user->id,
            'nombre' => $user->name,
            'email' => $user->email, // 👈 este sale de users_sbe.email
            'tipo_usuario' => match($user->id_tipo_usuario) {
                1 => 'Estudiante',
                2 => 'Personal Uleam',
                default => 'Otro',
            },
            'persona' => $persona,
            'detalles' => $datosExtra,
        ]);

    } catch (\Throwable $e) {
        Log::error("❌ Error al obtener userInfo: " . $e->getMessage());
        return response()->json([
            'error' => 'Error al obtener datos del usuario',
            'detalle' => $e->getMessage(),
        ], 500);
    }
}



}
