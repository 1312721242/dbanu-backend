<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\URL;
use Session;
use Illuminate\Http\Request;


class AuditoriaControllers extends Controller
{

   public function __construct()
    {
        $this->middleware('auth:api');
        $this->auditoriaController = new AuditoriaControllers();
        $this->logController = new LogController();
    }
    
   //funcion para auditar
    public function auditar($tabla, $campo, $dataOld, $dataNew, $tipo, $descripcion, $request = null)
    {
        $usuario = $request && !is_string($request) ? $request->user()->name : auth()->user()->name;
        $ip = $request && !is_string($request) ? $request->ip() : request()->ip();
        $ipv4 = gethostbyname(gethostname());
        $publicIp = file_get_contents('https://ifconfig.me/ip');
        //$publicIp = file_get_contents('https://ifconfig.me/ip');
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
            'aud_id_user' => $request && !is_string($request) ? $request->user()->id : auth()->user()->id
        ]);
    }

    public function getTipoAuditoria($tipo)
    {
        switch ($tipo) {
            case 'GET':
                return 1;
            case 'UPDATE':
                return 2;
            case 'INSERT':
                return 3;
            case 'DELETE':
                return 4;
            case 'LOGIN':
                return 5;
            case 'LOGOUT':
                return 6;
            case 'DISABLED':
                return 7;
            default:
                return 0;
        }
    }

    public function consultarAuditoriaGeneral(Request $request)
    {
       $data = DB::table('cpu_auditoria as a')
        ->leftJoin('users as u', 'a.aud_id_user', '=', 'u.id')
        ->select(
            'a.id_auditoria',
            'a.aud_user',
            'a.aud_tabla',
            'a.aud_campo',
            'a.aud_dataold',
            'a.aud_datanew',
            'a.aud_tipo',
            'a.aud_fecha',
            'a.aud_ip',
            'a.aud_tipoauditoria',
            'a.aud_descripcion',
            'a.aud_nombreequipo',
            'a.aud_descrequipo',
            'a.aud_codigo',
            'a.created_at as aud_created_at',
            'a.updated_at as aud_updated_at',
            'a.aud_id_user',
            'u.name as usuario_nombre',
            'u.email',
            'u.usr_tipo',
            'u.usr_estado',
            'u.usr_sede',
            'u.usr_facultad',
            'u.usr_profesion',
            'u.usr_cedula',
            'u.usr_carrera'
        )
        ->orderByDesc('a.id_auditoria')
        ->get();
        return response()->json($data);
        }

    public function consultarAuditoriaPorId($id)
    {
        $auditoria = DB::table('cpu_auditoria')->where('id', $id)->first();

        if (!$auditoria) {
            return response()->json(['message' => 'Auditoría no encontrada'], 404);
        }

        return response()->json($auditoria);
    }
}