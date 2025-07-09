<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CpuDiente;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
class CpuDienteController extends Controller
{
    public function buscarPorPaciente($id_paciente)
    {
        // Buscar el diente por el id del paciente
        $diente = CpuDiente::where('id_paciente', $id_paciente)->first();

        // Si no se encuentra el diente, devolver una respuesta indicando que no hay datos
        if (!$diente) {
            return response()->json([
                'message' => 'No se encontraron datos para este paciente',
                'arcada' => [
                    'adulto' => [],
                    'infantil' => []
                ]
            ], 204);
        }
        $arcada = $diente->arcada; // Eloquent ya lo convierte a array, no uses json_decode

        // Preparar la respuesta con los datos del paciente
        $respuesta = [
            'id_paciente' => $diente->id_paciente,
            'id_diente' => $diente->id,
            'arcada' => [
                'adulto' => $arcada['adulto'] ?? [],
                'infantil' => $arcada['infantil'] ?? []
            ]
        ];
        // $this->auditar('cpu_diente', 'buscarPorPaciente', '', $respuesta, 'CONSULTA', 'Consulta de diente por paciente', $id_paciente);
        return response()->json($respuesta, 200);
    }

    public function actualizarDiente(Request $request, $id_diente)
    {
        // Buscar el diente por su ID
        $diente = CpuDiente::find($id_diente);

        if (!$diente) {
            return response()->json(['message' => 'Diente no encontrado'], 204);
        }

        // Aquí ya no necesitas json_encode, porque se guardará como un array en la columna jsonb
        $diente->arcada = $request->input('arcada');
        $diente->save();
        $this->auditar('cpu_diente', 'actualizarDiente', '', $diente, 'MODIFICACION', 'Actualización de diente');
        return response()->json(['message' => 'Diente actualizado con éxito']);
    }

    //funcion para auditar
    private function auditar($tabla, $campo, $dataOld, $dataNew, $tipo, $descripcion, $request = null)
    {
        $usuario = $request && !is_string($request) ? $request->user()->name : auth()->user()->name;
        $ip = $request && !is_string($request) ? $request->ip() : request()->ip();
        $ipv4 = gethostbyname(gethostname());
        $publicIp = file_get_contents('https://ifconfig.me/ip');
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
