<?php

namespace App\Http\Controllers;

use App\Models\CpuMatriculaConfiguracion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class CpuMatriculaConfiguracionController extends Controller
{
    // public function __construct()
    // {
    //     $this->middleware('auth:api');
    // }
    public function index()
    {
        return CpuMatriculaConfiguracion::all();
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'id' => 'sometimes|required|integer|exists:cpu_matricula_configuracion,id',
            'id_periodo' => 'required|integer',
            'id_estado' => 'required|integer',
            'fecha_inicio_matricula_ordinaria' => 'required|date',
            'fecha_fin_matricula_ordinaria' => 'required|date',
            'fecha_inicio_matricula_extraordinaria' => 'required|date',
            'fecha_fin_matricula_extraordinaria' => 'required|date',
            'fecha_inicio_habil_login' => 'required|date',
            'fecha_fin_habil_login' => 'required|date',
        ]);

        $configuracion = CpuMatriculaConfiguracion::updateOrCreate(
            ['id' => $request->id], // Keys to find
            $validatedData // Values to fill or update
        );
        $this->auditar('cpu_matricula_configuracion', 'store', '', $configuracion, 'INSERCION', 'Creación de configuración de matrícula', $request);
        return response()->json($configuracion, 200);
    }


    public function show($id)
    {
        return CpuMatriculaConfiguracion::findOrFail($id);
    }

    public function fechasMatricula($id_periodo)
    {
        // Devuelve todos los registros donde 'id_periodo' es igual al parámetro recibido
        return CpuMatriculaConfiguracion::where('id_periodo', $id_periodo)->get();
    }

    public function periodoActivo()
    {
        return CpuMatriculaConfiguracion::where('id_estado', 8)->get();
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
