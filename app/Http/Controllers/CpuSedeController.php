<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\CpuSede;

class CpuSedeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }
    public function agregarSede(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre_sede' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $nombreSede = $request->input('nombre_sede');
        $usuario = $request->user()->name;
        $ip = $request->ip();
        $nombreequipo = gethostbyaddr($ip);
        $fecha = now();

        $sede = CpuSede::create([
            'nombre_sede' => $nombreSede,
        ]);

        DB::table('cpu_auditoria')->insert([
            'aud_user' => $usuario,
            'aud_tabla' => 'cpu_sede',
            'aud_campo' => 'nombre_sede',
            'aud_dataold' => '',
            'aud_datanew' => $nombreSede,
            'aud_tipo' => 'INSERCION',
            'aud_fecha' => $fecha,
            'aud_ip' => $ip,
            'aud_tipoauditoria' => 1,
            'aud_descripcion' => "CREACION DE SEDE $nombreSede",
            'aud_nombreequipo' => $nombreequipo,
        ]);

        return response()->json(['success' => true, 'message' => 'Sede agregada correctamente']);
    }

    public function modificarSede(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'nombre_sede' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $nombreSede = $request->input('nombre_sede');
        $usuario = $request->user()->name;
        $ip = $request->ip();
        $nombreequipo = gethostbyaddr($ip);
        $fecha = now();

        DB::table('cpu_sede')->where('id', $id)->update([
            'nombre_sede' => $nombreSede,
        ]);

        DB::table('cpu_auditoria')->insert([
            'aud_user' => $usuario,
            'aud_tabla' => 'cpu_sede',
            'aud_campo' => 'nombre_sede',
            'aud_dataold' => '',
            'aud_datanew' => $nombreSede,
            'aud_tipo' => 'MODIFICACION',
            'aud_fecha' => $fecha,
            'aud_ip' => $ip,
            'aud_tipoauditoria' => 2,
            'aud_descripcion' => "MODIFICACION DE SEDE $nombreSede",
            'aud_nombreequipo' => $nombreequipo,
        ]);

        return response()->json(['success' => true, 'message' => 'Sede modificada correctamente']);
    }

    public function eliminarSede(Request $request, $id)
    {
        $nombreSede = DB::table('cpu_sede')->where('id', $id)->value('nombre_sede');
        $usuario = $request->user()->name;
        $ip = $request->ip();
        $nombreequipo = gethostbyaddr($ip);
        $fecha = now();

        DB::table('cpu_sede')->where('id', $id)->delete();

        DB::table('cpu_auditoria')->insert([
            'aud_user' => $usuario,
            'aud_tabla' => 'cpu_sede',
            'aud_campo' => 'nombre_sede',
            'aud_dataold' => $nombreSede,
            'aud_datanew' => '',
            'aud_tipo' => 'ELIMINACION',
            'aud_fecha' => $fecha,
            'aud_ip' => $ip,
            'aud_tipoauditoria' => 3,
            'aud_descripcion' => "ELIMINACION DE SEDE $nombreSede",
            'aud_nombreequipo' => $nombreequipo,
        ]);

        return response()->json(['success' => true, 'message' => 'Sede eliminada correctamente']);
    }

    public function consultarSedes()
    {
        $sedes = DB::table('cpu_sede')->get();

        return response()->json($sedes);
    }
}
