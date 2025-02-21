<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CpuInsumoOcupado;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
class CpuInsumoOcupadoController extends Controller
{
    // Función para agregar un nuevo insumo ocupado
    public function store(Request $request)
    {
        $data = $request->validate([
            'id_insumo' => 'required|integer|exists:cpu_insumo,id',
            'id_funcionario' => 'required|integer|exists:users,id',
            'cantidad_ocupado' => 'required|numeric',
            'id_paciente' => 'required|integer|exists:cpu_personas,id',
            'detalle_ocupado' => 'nullable|string',
            'fecha_uso' => 'required|date',
        ]);

        $insumoOcupado = CpuInsumoOcupado::create($data);
        $this->auditar('cpu_insumo_ocupado', 'store', '', $insumoOcupado, 'INSERCION', 'Creación de insumo ocupado');
        return response()->json($insumoOcupado, 201);
    }

    // Función para consultar por rango de fechas
    public function getByDateRange(Request $request)
    {
        $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date',
        ]);

        $fechaInicio = Carbon::parse($request->input('fecha_inicio'))->startOfDay();
        $fechaFin = Carbon::parse($request->input('fecha_fin'))->endOfDay();

        $insumosOcupados = CpuInsumoOcupado::whereBetween('fecha_uso', [$fechaInicio, $fechaFin])->get();
        $this->auditar('cpu_insumo_ocupado', 'getByDateRange', '', $insumosOcupados, 'CONSULTA', 'Consulta de insumos ocupados por rango de fechas');
        return response()->json($insumosOcupados);
    }

    // Función para consultar por funcionario
    public function getByFuncionario($id_funcionario)
    {
        $insumosOcupados = CpuInsumoOcupado::where('id_funcionario', $id_funcionario)->get();
        $this->auditar('cpu_insumo_ocupado', 'getByFuncionario', '', $insumosOcupados, 'CONSULTA', 'Consulta de insumos ocupados por funcionario', $id_funcionario);
        return response()->json($insumosOcupados);
    }

    // Función para consultar por paciente
    public function getByPaciente($id_paciente)
    {
        $insumosOcupados = CpuInsumoOcupado::where('id_paciente', $id_paciente)->get();
        $this->auditar('cpu_insumo_ocupado', 'getByPaciente', '', $insumosOcupados, 'CONSULTA', 'Consulta de insumos ocupados por paciente', $id_paciente);
        return response()->json($insumosOcupados);
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
