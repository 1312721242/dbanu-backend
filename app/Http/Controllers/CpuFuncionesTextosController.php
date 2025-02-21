<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CpuFuncionesTextos;
use Illuminate\Support\Facades\DB;
class CpuFuncionesTextosController extends Controller
{
    public function index()
    {
        $funcionesTextos = CpuFuncionesTextos::all();
        return response()->json($funcionesTextos);
    }

    public function store(Request $request)
    {
        $funcionTexto = new CpuFuncionesTextos();
        $funcionTexto->descripcion = $request->descripcion;
        $funcionTexto->save();
        $this->auditar('cpu_funciones_textos', 'store', '', $funcionTexto, 'INSERCION', 'Creación de función de texto');
        return response()->json(['message' => 'Función de texto creada', 'funcionTexto' => $funcionTexto]);
    }

    public function show($id)
    {
        $funcionTexto = CpuFuncionesTextos::find($id);
        $this->auditar('cpu_funciones_textos', 'show', '', $funcionTexto, 'CONSULTA', 'Consulta de función de texto', $id);
        return response()->json($funcionTexto);
    }

    public function update(Request $request, $id)
    {
        $funcionTexto = CpuFuncionesTextos::find($id);
        $funcionTexto->descripcion = $request->descripcion;
        $funcionTexto->save();
        $this->auditar('cpu_funciones_textos', 'update', '', $funcionTexto, 'MODIFICACION', 'Actualización de función de texto');
        return response()->json(['message' => 'Función de texto actualizada', 'funcionTexto' => $funcionTexto]);
    }

    public function destroy($id)
    {
        $funcionTexto = CpuFuncionesTextos::find($id);
        $funcionTexto->delete();
        $this->auditar('cpu_funciones_textos', 'destroy', '', $funcionTexto, 'ELIMINACION', 'Eliminación de función de texto', $id);
        return response()->json(['message' => 'Función de texto eliminada']);
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
