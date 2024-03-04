<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\CpuProfesion;

class CpuProfesionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }
    public function agregarProfesion(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'profesion' => 'required|string|max:255',
            'abrebiatura' => 'required|string|max:10',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $profesion = $request->input('profesion');
        $abrebiatura = $request->input('abrebiatura');
        $usuario = $request->user()->name;
        $ip = $request->ip();
        $nombreequipo = gethostbyaddr($ip);
        $fecha = now();

        $newProfesion = CpuProfesion::create([
            'profesion' => $profesion,
            'abrebiatura' => $abrebiatura,
        ]);

        DB::table('cpu_auditoria')->insert([
            'aud_user' => $usuario,
            'aud_tabla' => 'cpu_profesion',
            'aud_campo' => 'profesion, abrebiatura',
            'aud_dataold' => '',
            'aud_datanew' => "$profesion, $abrebiatura",
            'aud_tipo' => 'INSERCION',
            'aud_fecha' => $fecha,
            'aud_ip' => $ip,
            'aud_tipoauditoria' => 1,
            'aud_descripcion' => "CREACION DE PROFESION $profesion con abreviatura $abrebiatura",
            'aud_nombreequipo' => $nombreequipo,
        ]);

        return response()->json(['success' => true, 'message' => 'Profesión agregada correctamente']);
    }

    public function modificarProfesion(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'profesion' => 'required|string|max:255',
            'abrebiatura' => 'required|string|max:10',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $profesion = $request->input('profesion');
        $abrebiatura = $request->input('abrebiatura');
        $usuario = $request->user()->name;
        $ip = $request->ip();
        $nombreequipo = gethostbyaddr($ip);
        $fecha = now();

        DB::table('cpu_profesion')->where('id', $id)->update([
            'profesion' => $profesion,
            'abrebiatura' => $abrebiatura,
        ]);

        DB::table('cpu_auditoria')->insert([
            'aud_user' => $usuario,
            'aud_tabla' => 'cpu_profesion',
            'aud_campo' => 'profesion, abrebiatura',
            'aud_dataold' => '',
            'aud_datanew' => "$profesion, $abrebiatura",
            'aud_tipo' => 'MODIFICACION',
            'aud_fecha' => $fecha,
            'aud_ip' => $ip,
            'aud_tipoauditoria' => 2,
            'aud_descripcion' => "MODIFICACION DE PROFESION $profesion con abreviatura $abrebiatura",
            'aud_nombreequipo' => $nombreequipo,
        ]);

        return response()->json(['success' => true, 'message' => 'Profesión modificada correctamente']);
    }

    public function eliminarProfesion(Request $request, $id)
    {
        $profesion = DB::table('cpu_profesion')->where('id', $id)->value('profesion');
        $usuario = $request->user()->name;
        $ip = $request->ip();
        $nombreequipo = gethostbyaddr($ip);
        $fecha = now();

        DB::table('cpu_profesion')->where('id', $id)->delete();

        DB::table('cpu_auditoria')->insert([
            'aud_user' => $usuario,
            'aud_tabla' => 'cpu_profesion',
            'aud_campo' => 'profesion',
            'aud_dataold' => $profesion,
            'aud_datanew' => '',
            'aud_tipo' => 'ELIMINACION',
            'aud_fecha' => $fecha,
            'aud_ip' => $ip,
            'aud_tipoauditoria' => 3,
            'aud_descripcion' => "ELIMINACION DE PROFESION $profesion",
            'aud_nombreequipo' => $nombreequipo,
        ]);

        return response()->json(['success' => true, 'message' => 'Profesión eliminada correctamente']);
    }

    public function consultarProfesiones()
    {
        $profesiones = DB::table('cpu_profesion')->get();

        return response()->json($profesiones);
    }
}
