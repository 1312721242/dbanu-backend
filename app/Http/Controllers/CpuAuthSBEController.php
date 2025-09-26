<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\SignupRequest;
use App\Models\CpuPersona;
use App\Models\UserSbe;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CpuAuthSBEController extends Controller
{

    public function consultarBienestar(Request $request)
    {
        try {
            $response = Http::asForm()->post('https://login.microsoftonline.com/31a17900-7589-4cfc-b11a-f4e83c27b8ed/oauth2/v2.0/token', [
                'grant_type' => 'client_credentials',
                'client_id' => '13e24fa4-9c64-4653-a96c-20964510b52a',
                'client_secret' => 'D1c8Q~gB11NpYVW7TBkTvoW1QSEHorolMBXcNcrs',
                'scope' => 'https://service.flow.microsoft.com//.default'
            ]);

            if ($response->failed()) {
                Log::error('Error al obtener el token de acceso: ' . $response->status() . ' ' . $response->body());
                return response()->json(['error' => 'Error al obtener el token de acceso'], 500);
            }

            $access_token = $response->json()['access_token'];
            $identificacion = $request->input('identificacion');

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type' => 'application/json'
            ])->post('https://prod-146.westus.logic.azure.com:443/workflows/033f8b54b4cc42f4ac0fdea481c0c27c/triggers/manual/paths/invoke?api-version=2016-06-01', [
                'identificacion' => $identificacion
            ]);

            if ($response->failed()) {
                Log::error('Error al enviar la solicitud a Azure Logic Apps: ' . $response->status() . ' ' . $response->body());
                return response()->json(['error' => 'Error al enviar la solicitud a Azure Logic Apps'], 500);
            }

            Log::info('Respuesta de Azure Logic Apps', $response->json());
            return response()->json($response->json());
        } catch (\Exception $e) {
            Log::error('Error al obtener el token de acceso: ' . $e->getMessage());
            return response()->json(['error' => 'Error al obtener el token de acceso'], 500);
        }
    }

    public function obtenerRegistroUsuario($email)
    {
        // Buscar primero en la tabla users_sbe
        $correo = DB::table('users_sbe')
            ->where('email', $email)
            ->value('email');

        // Si no existe en users_sbe, buscar en cpu_datos_estudiantes
        if (!$correo) {
            $correo = DB::table('cpu_datos_estudiantes')
                ->where('email_institucional', $email)
                ->orWhere('email_personal', $email)
                ->value('email_institucional');
            // 👆 aquí puedes cambiar a 'email_personal' si prefieres devolver ese
        }

        return response()->json([
            'user_email' => $correo
        ]);
    }

    public function signup(SignupRequest $request)
    {
        $data = $request->validated();
        Log::info('Datos recibidos para crear un nuevo usuario:', $data);

        // Verificar si el correo ya está en users_sbe
        if (UserSbe::where('email', $data['email'])->exists()) {
            return response()->json(['message' => 'El correo electrónico ya está registrado'], 422);
        }

        // Verificar si la persona ya está en cpu_personas (por cédula)
        if (CpuPersona::where('cedula', $data['cedula'])->exists()) {
            return response()->json(['message' => 'Esta persona ya está registrada en la tabla cpu_personas'], 422);
        }

        // Crear usuario en users_sbe
        $user = UserSbe::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => bcrypt($data['password']),
        ]);

        // Crear persona en cpu_personas
        CpuPersona::create([
            'cedula' => $data['cedula'],
            'nombres' => $data['nombres'],
            'direccion' => $data['direccion'],
            'celular' => $data['celular'] ?? null,
            'fechanaci' => $data['fechanaci'] ?? null,
            'sexo' => $data['sexo'],
            'estado_civil' => $data['estado_civil'],
            'nacionalidad' => $data['nacionalidad'],
            'provincia' => $data['provincia'],
            'ciudad' => $data['ciudad'],
            'parroquia' => $data['parroquia'] ?? null,
            'tipoetnia' => $data['tipoetnia'],
            'discapacidad' => $data['discapacidad'] ?? null,
            'id_tipo_usuario' => $data['id_tipo_usuario'],
        ]);

        return response()->json(['message' => 'Usuario registrado exitosamente'], 200);
    }
}
