<?php

namespace App\Http\Controllers;

use App\Models\CpuTipoUsuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class CpuTipoUsuarioController extends Controller
{
    public function index()
    {
        return CpuTipoUsuario::all();
    }

    public function store(Request $request)
    {
        $request->validate([
            'tipo_usuario' => 'required|string|max:255',
        ]);
        $this->auditar('cpu_tipo_usuario', 'store', '', $request->all(), 'INSERCION', 'Creación de tipo de usuario');
        return CpuTipoUsuario::create($request->all());
    }

    public function show($id)
    {
        return CpuTipoUsuario::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'tipo_usuario' => 'required|string|max:255',
        ]);

        $cpuTipoUsuario = CpuTipoUsuario::findOrFail($id);
        $cpuTipoUsuario->update($request->all());
        $this->auditar('cpu_tipo_usuario', 'update', '', $cpuTipoUsuario, 'MODIFICACION', 'Modificación de tipo de usuario', $id);
        return $cpuTipoUsuario;
    }

    public function destroy($id)
    {
        $cpuTipoUsuario = CpuTipoUsuario::findOrFail($id);
        $cpuTipoUsuario->delete();
        $this->auditar('cpu_tipo_usuario', 'destroy', '', $cpuTipoUsuario, 'ELIMINACION', 'Eliminación de tipo de usuario', $id);
        return response()->noContent();
    }

    public function filtrotipousuario($tipo_usu)
    {
        // Realiza la consulta en la base de datos
        $tiposUsuario = CpuTipoUsuario::where('clasificacion', $tipo_usu)->get();
        $this->auditar('cpu_tipo_usuario', 'filtrotipousuario', '', $tiposUsuario, 'CONSULTA', 'Consulta de tipos de usuario', $tipo_usu);
        // Retorna los resultados
        return response()->json($tiposUsuario);
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
