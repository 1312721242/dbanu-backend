<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\CpuFacultad;

class CpuFacultadController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }
    public function agregarFacultad(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_sede' => 'required|exists:cpu_sede,id',
            'fac_nombre' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $idSede = $request->input('id_sede');
        $facultadNombre = $request->input('fac_nombre');
        $usuario = $request->user()->name;
        $ip = $request->ip();
        $nombreequipo = gethostbyaddr($ip);
        $fecha = now();

        $facultad = CpuFacultad::create([
            'id_sede' => $idSede,
            'fac_nombre' => $facultadNombre,
        ]);

        DB::table('cpu_auditoria')->insert([
            'aud_user' => $usuario,
            'aud_tabla' => 'cpu_facultad',
            'aud_campo' => 'fac_nombre',
            'aud_dataold' => '',
            'aud_datanew' => $facultadNombre,
            'aud_tipo' => 'INSERCION',
            'aud_fecha' => $fecha,
            'aud_ip' => $ip,
            'aud_tipoauditoria' => 1,
            'aud_descripcion' => "CREACION DE FACULTAD $facultadNombre",
            'aud_nombreequipo' => $nombreequipo,
        ]);

        return response()->json(['success' => true, 'message' => 'Facultad agregada correctamente']);
    }

    public function modificarFacultad(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'fac_nombre' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $facultadNombre = $request->input('fac_nombre');
        $usuario = $request->user()->name;
        $ip = $request->ip();
        $nombreequipo = gethostbyaddr($ip);
        $fecha = now();

        DB::table('cpu_facultad')->where('id', $id)->update([
            'fac_nombre' => $facultadNombre,
        ]);

        DB::table('cpu_auditoria')->insert([
            'aud_user' => $usuario,
            'aud_tabla' => 'cpu_facultad',
            'aud_campo' => 'fac_nombre',
            'aud_dataold' => '',
            'aud_datanew' => $facultadNombre,
            'aud_tipo' => 'MODIFICACION',
            'aud_fecha' => $fecha,
            'aud_ip' => $ip,
            'aud_tipoauditoria' => 2,
            'aud_descripcion' => "MODIFICACION DE FACULTAD $facultadNombre",
            'aud_nombreequipo' => $nombreequipo,
        ]);

        return response()->json(['success' => true, 'message' => 'Facultad modificada correctamente']);
    }

    public function eliminarFacultad(Request $request, $id)
    {
        $facultad = DB::table('cpu_facultad')->where('id', $id)->value('fac_nombre');
        $usuario = $request->user()->name;
        $ip = $request->ip();
        $nombreequipo = gethostbyaddr($ip);
        $fecha = now();

        DB::table('cpu_facultad')->where('id', $id)->delete();

        DB::table('cpu_auditoria')->insert([
            'aud_user' => $usuario,
            'aud_tabla' => 'cpu_facultad',
            'aud_campo' => 'fac_nombre',
            'aud_dataold' => $facultad,
            'aud_datanew' => '',
            'aud_tipo' => 'ELIMINACION',
            'aud_fecha' => $fecha,
            'aud_ip' => $ip,
            'aud_tipoauditoria' => 3,
            'aud_descripcion' => "ELIMINACION DE FACULTAD $facultad",
            'aud_nombreequipo' => $nombreequipo,
        ]);

        return response()->json(['success' => true, 'message' => 'Facultad eliminada correctamente']);
    }

    public function consultarFacultades()
    {
        $facultades = DB::table('cpu_facultad')->get();

        return response()->json($facultades);
    }

    public function consultarFacultadesporSede($id_sede)
    {
        $facultades = DB::table('cpu_facultad')
            ->where('id_sede', $id_sede)
            ->get();

        return response()->json($facultades);
    }

}
