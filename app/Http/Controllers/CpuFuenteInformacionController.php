<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CpuFuenteInformacionController extends Controller
{
  public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function agregarFuenteInformacion(Request $request)
{
    $validator = Validator::make($request->all(), [
        'id_objetivo' => 'required|integer',
        'descripcion' => 'required|string|unique:cpu_fuente_informacion',
    ]);

    if ($validator->fails()) {
        return response()->json(['error' => $validator->errors()], 400);
    }

    $id_objetivo = $request->input('id_objetivo');
    $descripcion = $request->input('descripcion');
    $usuario = $request->user()->name;
    $ip = $request->ip();
    $nombreequipo = gethostbyaddr($ip);
    $fecha = now();
    try {
        $fuenteInformacion = CpuFuenteInformacion::create([
            'id_objetivo' => $id_objetivo,
            'descripcion' => $descripcion,
        ]);

        DB::table('cpu_auditoria')->insert([
            'aud_user' => $usuario,
            'aud_tabla' => 'cpu_fuente_informacion',
            'aud_campo' => 'descripcion',
            'aud_dataold' => '',
            'aud_datanew' => "$id_objetivo,$descripcion",
            'aud_tipo' => 'INSERCIÓN',
            'aud_fecha' => $fecha,
            'aud_ip' => $ip,
            'aud_tipoauditoria' => 1,
            'aud_descripcion' => "CREACION DE OBJETIVO $id_objetivo,$descripcion",
            'aud_nombreequipo' => $nombreequipo,
            'created_at' =>$fecha,
            'updated_at' =>$fecha,
        ]);

        return response()->json(['success' => true, 'message' => 'Objetivo agregada correctamente']);
    } catch (\Throwable $th) {
        return response()->json(['warning' => true, 'message' => 'El Objetivo que intentas registrar ya existe']);
    }
}

public function modificarFuenteInformacion(Request $request, $id)
{
    $validator = Validator::make($request->all(), [
        'id_objetivo' => 'required|integer',
        'descripcion' => 'required|string|unique:cpu_fuente_informacion',
    ]);

    if ($validator->fails()) {
        return response()->json(['error' => $validator->errors()], 400);
    }

    $id_objetivo = $request->input('id_objetivo');
    $descripcion = $request->input('descripcion');
    $usuario = $request->user()->name;
    $ip = $request->ip();
    $nombreequipo = gethostbyaddr($ip);
    $fecha = now();
    try {
        DB::table('cpu_fuente_informacion')->where('id', $id)->update([
            'id_objetivo' => $id_objetivo,
            'descripcion' => $descripcion,
        ]);

        DB::table('cpu_auditoria')->insert([
            'aud_user' => $usuario,
            'aud_tabla' => 'cpu_fuente_informacion',
            'aud_campo' => 'descripcion',
            'aud_dataold' => '',
            'aud_datanew' => "$id_objetivo,$descripcion",
            'aud_tipo' => 'MODIFICACIÓN',
            'aud_fecha' => $fecha,
            'aud_ip' => $ip,
            'aud_tipoauditoria' => 2,
            'aud_descripcion' => "MODIFICACIÓN DE OBJETIVO $id_objetivo,$descripcion",
            'aud_nombreequipo' => $nombreequipo,
            'created_at' =>$fecha,
            'updated_at' =>$fecha,
        ]);

        return response()->json(['success' => true, 'message' => 'Objetivo modificada correctamente']);
    } catch (\Throwable $th) {
        return response()->json(['warning' => true, 'message' => 'Ya existe un registro con este objetivo por lo que la modificación que intentas hacer ha sido abortada']);
    }
}

public function eliminarFuenteInformacion(Request $request, $id)
{
    $descripcion = DB::table('cpu_fuente_informacion')->where('id', $id)->value('descripcion');
    $usuario = $request->user()->name;
    $ip = $request->ip();
    $nombreequipo = gethostbyaddr($ip);
    $fecha = now();

    DB::table('cpu_fuente_informacion')->where('id', $id)->delete();

    DB::table('cpu_auditoria')->insert([
        'aud_user' => $usuario,
        'aud_tabla' => 'cpu_fuente_informacion',
        'aud_campo' => 'descripcion',
        'aud_dataold' => $descripcion,
        'aud_datanew' => '',
        'aud_tipo' => 'ELIMINACIÓN',
        'aud_fecha' => $fecha,
        'aud_ip' => $ip,
        'aud_tipoauditoria' => 3,
        'aud_descripcion' => "ELIMINACIÓN DE OBJETIVO $descripcion",
        'aud_nombreequipo' => $nombreequipo,
        'created_at' =>$fecha,
        'updated_at' =>$fecha,
    ]);

    return response()->json(['success' => true, 'message' => 'Objetivo eliminado correctamente']);
}

public function consultarFuenteInformacion(){
    $objetivos = DB::table('cpu_fuente_informacion')->get();
    return response()->json($objetivos);
}

}
