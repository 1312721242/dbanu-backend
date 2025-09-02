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
        // 1) Validar que la atención exista en la tabla padre
        $request->validate([
            'id_atencion' => 'required|integer|exists:cpu_atenciones,id',
        ], [
            'id_atencion.required' => 'El parámetro id_atencion es obligatorio.',
            'id_atencion.integer'  => 'El parámetro id_atencion debe ser un número entero.',
            'id_atencion.exists'   => 'La atención indicada no existe.',
        ]);

        $idAtencion = (int) $request->input('id_atencion');

        // 2) Buscar el triaje por id_atencion en cpu_atenciones_triaje
        $triaje = CpuAtencionTriaje::where('id_atencion', $idAtencion)->first();

        if (!$triaje) {
            // 204 no debe llevar body; mejor 404 con mensaje
            return response()->json([
                'message' => 'No existen datos de triaje para esta atención.',
            ], 404);
        }

        // 3) Auditoría (verifica el nombre real de la tabla que usas en tu auditoría)
        $this->auditar(
            'public.cpu_atenciones_triaje',
            'id_atencion',
            '',
            $idAtencion,
            'CONSULTA',
            "CONSULTA DE TRIAJE POR DERIVACION: {$idAtencion}"
        );

        // 4) Respuesta
        return response()->json([
            'id_derivacion'       => $triaje->id_atencion,
            'talla'               => $triaje->talla,
            'peso'                => $triaje->peso,
            'temperatura'         => $triaje->temperatura,
            'saturacion'          => $triaje->saturacion,
            'presion_sistolica'   => $triaje->presion_sistolica,
            'presion_diastolica'  => $triaje->presion_diastolica,
            // Si quieres incluir calculados que guardas como texto:
            'imc'                 => $triaje->imc,
            'peso_ideal'          => $triaje->peso_ideal,
            'estado_paciente'     => $triaje->estado_paciente,
        ], 200);
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
        $codigo_auditoria = strtoupper($tabla . '_' . $campo . '_' . $tipo);
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
