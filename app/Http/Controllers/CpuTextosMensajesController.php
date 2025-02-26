<?php

namespace App\Http\Controllers;

use App\Models\CpuTextosMensajes;
use App\Models\CpuFuncionesTextos;
use Illuminate\Support\Facades\DB;

use Illuminate\Http\Request;

class CpuTextosMensajesController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }
    public function obtenerTextosFuncionTres()
    {
        $textos = CpuTextosMensajes::where('id_funciones_texto', 3)
            ->join('cpu_funciones_textos', 'cpu_textos_mensajes.id_funciones_texto', '=', 'cpu_funciones_textos.id')
            ->select('cpu_textos_mensajes.*', 'cpu_funciones_textos.descripcion')
            ->orderBy('id', 'asc')
            ->get();
        $this->auditar('cpu_textos_mensajes', 'obtenerTextosFuncionTres', '', $textos, 'CONSULTA', 'Consulta de textos de la función 3');
        return response()->json($textos);
    }


    public function editarTextosFuncionTres(Request $request)
    {
        // Asumiendo que recibimos un array de datos en el cuerpo de la solicitud
        $datos = $request->all();

        // Verificar si los datos son un solo array o un array de arrays
        if (!is_array(reset($datos))) {
            // Si son un solo array, convertirlo en un array de arrays
            $datos = [$datos];
        }

        // Iterar sobre cada conjunto de datos
        foreach ($datos as $dato) {
            $id = $dato['id'];
            $nuevoTexto = $dato['texto'];

            // Actualizar el texto en la base de datos para el ID proporcionado
            CpuTextosMensajes::where('id', $id)
                ->update(['texto' => $nuevoTexto]);
        }
        $this->auditar('cpu_textos_mensajes', 'editarTextosFuncionTres', '', $datos, 'MODIFICACION', 'Actualización de textos de la función 3');
        return response()->json(['mensaje' => 'Textos actualizados con éxito.']);
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
