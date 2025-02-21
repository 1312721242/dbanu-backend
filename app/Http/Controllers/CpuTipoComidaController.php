<?php

namespace App\Http\Controllers;

use App\Models\CpuTipoComida;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;

class CpuTipoComidaController extends Controller
{
    public function index()
    {
        $tiposComida = CpuTipoComida::all();

        // Verificar si ya se ha registrado una auditoría reciente para evitar duplicados
        $cacheKey = 'auditoria_cpu_tipo_comida_index';
        if (!Cache::has($cacheKey)) {
            // Auditoría
            $this->auditar('cpu_tipo_comida', 'index', '', '', 'CONSULTA', "CONSULTA DE TODOS LOS TIPOS DE COMIDA");
            // Almacenar en caché por un corto periodo de tiempo
            Cache::put($cacheKey, true, now()->addSeconds(10));
        }

        return response()->json($tiposComida);
    }

    public function store(Request $request)
    {
        $request->validate([
            'descripcion' => 'required|string|max:255',
        ]);

        $cpuTipoComida = CpuTipoComida::create($request->all());

        // Auditoría
        $this->auditar('cpu_tipo_comida', 'descripcion', '', $cpuTipoComida->descripcion, 'INSERCION', "INSERCION DE NUEVO TIPO DE COMIDA: {$cpuTipoComida->descripcion}", $request);

        return response()->json($cpuTipoComida, 201);
    }

    public function show($id)
    {
        $cpuTipoComida = CpuTipoComida::find($id);

        if (is_null($cpuTipoComida)) {
            return response()->json(['message' => 'Record not found'], 404);
        }

        // Auditoría
        $this->auditar('cpu_tipo_comida', 'show', '', '', 'CONSULTA', "CONSULTA DE TIPO DE COMIDA ID: $id");

        return response()->json($cpuTipoComida);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'descripcion' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $descripcion = $request->input('descripcion');

        $tipoComidaActual = CpuTipoComida::find($id);
        if (!$tipoComidaActual) {
            return response()->json(['error' => 'Tipo de comida no encontrado'], 404);
        }

        // Guarda la descripción anterior antes de actualizar
        $descripcionAnterior = $tipoComidaActual->descripcion;

        // Actualiza el tipo de comida con la nueva descripción
        $tipoComidaActual->descripcion = $descripcion;
        $tipoComidaActual->save();

        // Auditoría
        $this->auditar('cpu_tipo_comida', 'descripcion', $descripcionAnterior, $descripcion, 'MODIFICACION', "MODIFICACION DE DESCRIPCION $descripcion", $request);

        return response()->json(['success' => true, 'message' => 'Tipo de comida actualizado correctamente']);
    }

    public function destroy($id)
    {
        $cpuTipoComida = CpuTipoComida::find($id);

        if (is_null($cpuTipoComida)) {
            return response()->json(['message' => 'Record not found'], 404);
        }

        $descripcionAnterior = $cpuTipoComida->descripcion;
        $cpuTipoComida->delete();

        // Auditoría
        $this->auditar('cpu_tipo_comida', 'descripcion', $descripcionAnterior, '', 'ELIMINACION', "ELIMINACION DE TIPO DE COMIDA: $descripcionAnterior");

        return response()->json(null, 204);
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
            default:
                return 0;
        }
    }
}
