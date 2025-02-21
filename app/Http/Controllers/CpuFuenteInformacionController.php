<?php

namespace App\Http\Controllers;

use App\Models\CpuFuenteInformacion;
use App\Models\CpuIndicador;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class CpuFuenteInformacionController extends Controller
{
    public function getFuenteInformacion($id_indicador)
    {
        // Consulta las fuentes basadas en el id_indicador
        $fuentes = CpuFuenteInformacion::where('id_indicador', $id_indicador)->get();
        $this->auditar('cpu_fuente_informacion', 'getFuenteInformacion', '', $fuentes, 'CONSULTA', 'Consulta de fuentes de información', $id_indicador);
        return response()->json($fuentes);
    }

    public function storeFuenteInformacion(Request $request)
    {
        // Validar los datos recibidos
        $validatedData = $request->validate([
            'id_indicador' => 'required|exists:cpu_indicador,id',
            'descripcion' => 'required|string',
        ]);

        // Crear la nueva fuente de información
        $fuente = CpuFuenteInformacion::create([
            'id_indicador' => $validatedData['id_indicador'],
            'descripcion' => $validatedData['descripcion'],
        ]);
        $this->auditar('cpu_fuente_informacion', 'storeFuenteInformacion', '', $fuente, 'INSERCION', 'Creación de fuente de información');
        // Retornar una respuesta en formato JSON
        return response()->json([
            'message' => 'Fuente de información creada exitosamente.',
            'data' => $fuente,
        ], 201);
    }

    public function updateFuenteInformacion(Request $request, $id)
    {
        // Validar los datos recibidos
        $validatedData = $request->validate([
            'id_indicador' => 'required|exists:cpu_indicador,id',
            'descripcion' => 'required|string',
        ]);

        // Buscar la fuente de información por ID
        $fuente = CpuFuenteInformacion::find($id);

        if (!$fuente) {
            return response()->json([
                'message' => 'Fuente de información no encontrada.',
            ], 404);
        }

        // Actualizar la fuente de información
        $fuente->id_indicador = $validatedData['id_indicador'];
        $fuente->descripcion = $validatedData['descripcion'];
        $fuente->save();
        $this->auditar('cpu_fuente_informacion', 'updateFuenteInformacion', '', $fuente, 'MODIFICACION', 'Actualización de fuente de información');
        // Retornar una respuesta en formato JSON
        return response()->json([
            'message' => 'Fuente de información actualizada exitosamente.',
            'data' => $fuente,
        ], 200);
    }
    //funcion para auditar
    private function auditar($tabla, $campo, $dataOld, $dataNew, $tipo, $descripcion, $request = null)
    {
        $usuario = $request && !is_string($request) ? $request->user()->name : auth()->user()->name;
        $ip = $request && !is_string($request) ? $request->ip() : request()->ip();
        $ipv4 = gethostbyname(gethostname());
        $publicIp = file_get_contents('http://ipecho.net/plain');
        $ioConcatenadas = 'IP LOCAL: ' . $ip . '  --IPV4: ' . $ipv4 . '  --IP PUBLICA: ' . $publicIp;
        $nombreequipo = gethostbyaddr($ip);
        $userAgent = $request && !is_string($request) ? $request->header('User-Agent') : request()->header('User-Agent');
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
            case 'DESACTIVACION':
                return 7;
            default:
                return 0;
        }
    }

}
