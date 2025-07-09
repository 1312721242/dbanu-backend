<?php

namespace App\Http\Controllers;

use App\Models\CpuTipoDiscapacidad;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class CpuTipoDiscapacidadController extends Controller
{
    public function index()
    {
        return CpuTipoDiscapacidad::all();
    }

    public function store(Request $request)
    {
        $request->validate([
            'descripcion' => 'required|string|max:255',
        ]);
        $this->auditar('cpu_tipo_discapacidad', 'store', '', $request->all(), 'INSERCION', 'Creación de tipo de discapacidad');
        return CpuTipoDiscapacidad::create($request->all());
    }

    public function show(CpuTipoDiscapacidad $cpuTipoDiscapacidad)
    {
        return $cpuTipoDiscapacidad;
    }

    public function update(Request $request, CpuTipoDiscapacidad $cpuTipoDiscapacidad)
    {
        $request->validate([
            'descripcion' => 'required|string|max:255',
        ]);

        $cpuTipoDiscapacidad->update($request->all());
        $this->auditar('cpu_tipo_discapacidad', 'update', '', $cpuTipoDiscapacidad, 'MODIFICACION', 'Modificación de tipo de discapacidad');
        return $cpuTipoDiscapacidad;
    }

    public function destroy(CpuTipoDiscapacidad $cpuTipoDiscapacidad)
    {
        $cpuTipoDiscapacidad->delete();
        $this->auditar('cpu_tipo_discapacidad', 'destroy', '', $cpuTipoDiscapacidad, 'ELIMINACION', 'Eliminación de tipo de discapacidad', $cpuTipoDiscapacidad);
        return response()->noContent();
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
