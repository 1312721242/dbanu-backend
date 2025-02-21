<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CpuEstandar;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class CpuEstandarController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Este método podría ser utilizado para devolver todos los registros, si es necesario
        $estandares = CpuEstandar::all();
        return response()->json($estandares);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'id_indicador' => 'required|exists:cpu_indicador,id',
            'descripcion' => 'required|string',
        ]);

        $estandar = new CpuEstandar();
        $estandar->id_indicador = $request->input('id_indicador');
        $estandar->descripcion = $request->input('descripcion');
        $estandar->save();
        $this->auditar('cpu_estandar', 'store', '', $estandar, 'INSERCION', 'Creación de estándar', $request);
        return response()->json([
            'message' => 'Estandar creado exitosamente',
            'estandar' => $estandar
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */

    public function edit(Request $request, $id)
    {
        // Validar los datos de la solicitud
        $validator = Validator::make($request->all(), [
            'id_indicador' => 'required|integer|exists:cpu_indicador,id',
            'descripcion' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        // Buscar el estándar por ID
        $estandar = CpuEstandar::find($id);
        if (!$estandar) {
            return response()->json(['error' => 'Estándar no encontrado'], 404);
        }

        // Actualizar los datos del estándar
        $estandar->id_indicador = $request->input('id_indicador');
        $estandar->descripcion = $request->input('descripcion');
        $estandar->save();
        $this->auditar('cpu_estandar', 'edit', '', $estandar, 'MODIFICACION', 'Actualización de estándar', $request);
        return response()->json(['message' => 'Estándar actualizado exitosamente'], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    /**
     * Obtener los estándares por año e indicador.
     */
    public function obtenerEstandares($id_year, $id_indicador)
    {
        $estandares = CpuEstandar::whereHas('indicador', function($query) use ($id_year, $id_indicador) {
            $query->where('id_year', $id_year)
                  ->where('id', $id_indicador);
        })->get();
        $this->auditar('cpu_estandar', 'obtenerEstandares', '', $estandares, 'CONSULTA', 'Consulta de estándares por año e indicador', $id_year, $id_indicador);
        return response()->json($estandares);
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
