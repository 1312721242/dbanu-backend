<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\CpuEstado; // Importa el modelo CpuEstado
use Illuminate\Routing\Controller; // Asegúrate de tener esta línea de importación

class CpuEstadosController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function agregarEstado(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'estado' => 'required|string|unique:cpu_estados',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $estado = $request->input('estado');

        $newEstado = CpuEstado::create([
            'estado' => $estado,
        ]);

        // Auditoría
        $usuario = $request->user()->name;
        $ip = $request->ip();
        $nombreequipo = gethostbyaddr($ip);
        $fecha = now();

        DB::table('cpu_auditoria')->insert([
            'aud_user' => $usuario,
            'aud_tabla' => 'cpu_estados',
            'aud_campo' => 'estado',
            'aud_dataold' => '',
            'aud_datanew' => $estado,
            'aud_tipo' => 'INSERCION',
            'aud_fecha' => $fecha,
            'aud_ip' => $ip,
            'aud_tipoauditoria' => 1,
            'aud_descripcion' => "CREACION DE ESTADO $estado",
            'aud_nombreequipo' => $nombreequipo,
        ]);

        return response()->json(['success' => true, 'message' => 'Estado agregado correctamente']);
    }

    public function modificarEstado(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'estado' => 'required|string|unique:cpu_estados',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $estado = $request->input('estado');

        $estadoActual = CpuEstado::find($id);
        if (!$estadoActual) {
            return response()->json(['error' => 'Estado no encontrado'], 404);
        }

        $estadoActual->estado = $estado;
        $estadoActual->save();

        // Auditoría
        $usuario = $request->user()->name;
        $ip = $request->ip();
        $nombreequipo = gethostbyaddr($ip);
        $fecha = now();

        DB::table('cpu_auditoria')->insert([
            'aud_user' => $usuario,
            'aud_tabla' => 'cpu_estados',
            'aud_campo' => 'estado',
            'aud_dataold' => $estadoActual->estado,
            'aud_datanew' => $estado,
            'aud_tipo' => 'MODIFICACION',
            'aud_fecha' => $fecha,
            'aud_ip' => $ip,
            'aud_tipoauditoria' => 2,
            'aud_descripcion' => "MODIFICACION DE ESTADO $estado",
            'aud_nombreequipo' => $nombreequipo,
        ]);

        return response()->json(['success' => true, 'message' => 'Estado modificado correctamente']);
    }

    public function eliminarEstado(Request $request, $id)
    {
        $estado = CpuEstado::find($id);
        if (!$estado) {
            return response()->json(['error' => 'Estado no encontrado'], 404);
        }

        $estado->delete();

        // Auditoría
        $usuario = $request->user()->name;
        $ip = $request->ip();
        $nombreequipo = gethostbyaddr($ip);
        $fecha = now();

        DB::table('cpu_auditoria')->insert([
            'aud_user' => $usuario,
            'aud_tabla' => 'cpu_estados',
            'aud_campo' => 'estado',
            'aud_dataold' => $estado->estado,
            'aud_datanew' => '',
            'aud_tipo' => 'ELIMINACION',
            'aud_fecha' => $fecha,
            'aud_ip' => $ip,
            'aud_tipoauditoria' => 3,
            'aud_descripcion' => "ELIMINACION DE ESTADO $estado->estado",
            'aud_nombreequipo' => $nombreequipo,
        ]);

        return response()->json(['success' => true, 'message' => 'Estado eliminado correctamente']);
    }

    public function consultarEstados()
    {
        $estados = CpuEstado::all();

        return response()->json($estados);
    }
}
