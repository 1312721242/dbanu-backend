<?php

namespace App\Http\Controllers;

use App\Models\CpuAtencion;
use Illuminate\Http\Request;
use App\Models\CpuAtencionTriaje;
use App\Models\CpuDerivacion;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class CpuAtencionTriajeController extends Controller
{
    public function obtenerTallaPesoPaciente($id_paciente)
    {
        // Buscar todas las atenciones del paciente ordenadas de forma descendente por ID
        $atenciones = CpuAtencion::where('id_persona', $id_paciente)
            ->orderBy('id', 'desc')
            ->pluck('id');

        if ($atenciones->isEmpty()) {
            return response()->json(['error' => 'No se encontraron atenciones para el paciente'], 204);
        }

        // Iterar sobre los id_atencion en orden descendente y buscar el primer triaje disponible
        foreach ($atenciones as $idAtencion) {
            $triaje = CpuAtencionTriaje::where('id_atencion', $idAtencion)
                ->orderBy('updated_at', 'desc')
                ->first();

            if ($triaje) {
                // Auditoría
                $this->auditar('cpu_atencion_triaje', 'id_atencion', '', $idAtencion, 'CONSULTA', "CONSULTA DE TRIAJE PARA ATENCION: {$idAtencion}");

                // Si encontramos un triaje, devolvemos los datos
                return response()->json([
                    'id' => $triaje->id,
                    'talla' => $triaje->talla,
                    'peso' => $triaje->peso,
                    'temperatura' => $triaje->temperatura,
                    'saturacion' => $triaje->saturacion,
                    'presion_sistolica' => $triaje->presion_sistolica,
                    'presion_diastolica' => $triaje->presion_diastolica
                ]);
            }
        }

        // Si no se encontró ningún triaje, devolver error
        return response()->json(['error' => 'No se encontraron datos de triaje para las atenciones registradas'], 204);
    }

    public function obtenerDatosTriajePorDerivacion(Request $request)
    {
        // Validar los parámetros de entrada
        $request->validate([
            'id_atencion' => 'required|integer|exists:cpu_atenciones_triaje,id_atencion'
        ]);

        // Obtener el id_derivacion de la solicitud
        $idAtencion = $request->input('id_atencion');

        // Obtener los datos de triaje correspondientes a la derivación
        $triaje = CpuAtencionTriaje::where('id_atencion', $idAtencion)->first();

        if (!$triaje) {
            return response()->json(['error' => 'Datos de triaje no encontrados para la derivación'], 204);
        }

        // Auditoría
        $this->auditar('cpu_atencion_triaje', 'id_atencion', '', $idAtencion, 'CONSULTA', "CONSULTA DE TRIAJE POR DERIVACION: {$idAtencion}");

        // Devolver los datos de triaje como respuesta JSON
        return response()->json([
            'id_derivacion' => $triaje->id_atencion,
            'talla' => $triaje->talla,
            'peso' => $triaje->peso,
            'temperatura' => $triaje->temperatura,
            'saturacion' => $triaje->saturacion,
            'presion_sistolica' => $triaje->presion_sistolica,
            'presion_diastolica' => $triaje->presion_diastolica,
        ]);
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
            case 'DESACTIVACION':
                return 7;
            default:
                return 0;
        }
    }
}
