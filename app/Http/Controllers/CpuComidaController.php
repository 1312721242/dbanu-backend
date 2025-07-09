<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CpuComida;
use Illuminate\Support\Facades\DB;

class CpuComidaController extends Controller
{

    public function index(Request $request)
    {
        $query = CpuComida::with(['tipoComida', 'sede', 'facultad']);

        // Si el usuario es tipo 1 (Administrador) => trae todo
        if ($request->usr_tipo == 1) {
            // No se aplican filtros
        }

        // Si el usuario es tipo 20 => trae todos los registros de su sede (todas las facultades)
        elseif ($request->usr_tipo == 20 && $request->has('id_sede')) {
            $query->where('id_sede', $request->id_sede);
        }

        // Si el usuario es tipo 6 => trae registros de su sede y su facultad
        elseif ($request->usr_tipo == 6 && $request->has('id_sede') && $request->has('id_facultad')) {
            $query->where('id_sede', $request->id_sede)
                  ->where('id_facultad', $request->id_facultad);
        }

        $comidas = $query->get()->map(function ($comida) {
            return [
                'id_comida' => $comida->id,
                'id_tipo_comida' => $comida->id_tipo_comida,
                'descripcion_comida' => $comida->descripcion,
                'descripcion_tipo_comida' => $comida->tipoComida->descripcion,
                'precio' => $comida->precio,
                'id_sede' => $comida->id_sede,
                'nombre_sede' => $comida->sede->nombre_sede ?? null,
                'id_facultad' => $comida->id_facultad,
                'fac_nombre' => $comida->facultad->fac_nombre ?? null,
            ];
        });

        $this->auditar('cpu_comida', 'id', '', '', 'CONSULTA', "CONSULTA DE COMIDAS");

        return response()->json($comidas);
    }

    public function indexTipoComida(Request $request)
    {
        $query = CpuComida::with('tipoComida');

        // Aplicar filtros si el usuario NO es admin
        if ($request->usr_tipo != 1) {
            if ($request->has('id_sede')) {
                $query->where('id_sede', $request->id_sede);
            }

            if ($request->has('id_facultad')) {
                $query->where('id_facultad', $request->id_facultad);
            }
        }

        $comidas = $query->get();

        // Agrupar por tipo de comida
        $comidasAgrupadas = $comidas->groupBy('tipoComida.descripcion');

        // Formatear la respuesta
        $response = [];
        foreach ($comidasAgrupadas as $tipoComida => $comidas) {
            $comidasFormateadas = $comidas->map(function ($comida) {
                return [
                    'id_comida' => $comida->id,
                    'descripcion_comida' => $comida->descripcion,
                    'precio' => $comida->precio,
                ];
            })->toArray();

            $response[] = [
                'tipo_comida' => $tipoComida,
                'comidas' => $comidasFormateadas,
            ];
        }

        // Auditoría
        $this->auditar('cpu_comida', 'id', '', '', 'CONSULTA', "CONSULTA DE COMIDAS AGRUPADAS POR TIPO");

        return response()->json($response);
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_tipo_comida' => 'required|exists:cpu_tipo_comida,id',
            'descripcion' => 'required|string|max:255',
            'precio' => 'required|numeric',
            'id_sede' => 'required|numeric',
            'id_facultad' => 'required|numeric',
        ]);

        $comida = CpuComida::create($request->all());

        // Auditoría
        $this->auditar('cpu_comida', 'id', '', '', 'INSERCION', "INSERCION DE COMIDA: {$comida->id}");

        return response()->json($comida, 201);
    }

    public function show($id)
    {
        $comida = CpuComida::findOrFail($id);
        return response()->json($comida);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'id_tipo_comida' => 'required|exists:cpu_tipo_comida,id',
            'descripcion' => 'required|string|max:255',
            'precio' => 'required|numeric',
        ]);

        $comida = CpuComida::findOrFail($id);
        $comida->update($request->all());

        return response()->json($comida, 200);
    }

    public function destroy($id)
    {
        $comida = CpuComida::findOrFail($id);
        $comida->delete();

        return response()->json(null, 204);
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
