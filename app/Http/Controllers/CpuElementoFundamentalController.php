<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\CpuEstandar;
use App\Models\CpuElementoFundamental;


class CpuElementoFundamentalController extends Controller
{
  public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function agregarFuenteInformacione(Request $request)
{
    $validator = Validator::make($request->all(), [
        'id_estandar' => 'required|integer',
        'id_sede' => 'required|integer',
        'descripcion' => 'required|string',
    ]);

    if ($validator->fails()) {
        return response()->json(['error' => $validator->errors()], 400);
    }

    $id_estandar = $request->input('id_estandar');
    $id_sede = $request->input('id_sede');
    $descripcion = $request->input('descripcion');
    $usuario = $request->user()->name;
    $ip = $request->ip();
    $nombreequipo = gethostbyaddr($ip);
    $fecha = now();

        $fuenteInformacion = CpuElementoFundamental::create([
            'id_estandar' => $id_estandar,
            'id_sede' => $id_sede,
            'descripcion' => $descripcion,
        ]);

        DB::table('cpu_auditoria')->insert([
            'aud_user' => $usuario,
            'aud_tabla' => 'cpu_elemento_fundamental',
            'aud_campo' => 'descripcion',
            'aud_dataold' => '',
            'aud_datanew' => "$id_estandar,$descripcion",
            'aud_tipo' => 'INSERCIÓN',
            'aud_fecha' => $fecha,
            'aud_ip' => $ip,
            'aud_tipoauditoria' => 1,
            'aud_descripcion' => "CREACION DE FUENTE DE INFORMACION $id_estandar,$descripcion,$id_sede",
            'aud_nombreequipo' => $nombreequipo,
            'created_at' =>$fecha,
            'updated_at' =>$fecha,
        ]);

        return response()->json(['success' => true, 'message' => 'Fuente de información agregada correctamente']);

}

public function modificarFuenteInformacion(Request $request, $id)
{
    $validator = Validator::make($request->all(), [
        'id_estandar' => 'required|integer',
        'id_sede' => 'required|integer',
        'descripcion' => 'required|string',
    ]);

    if ($validator->fails()) {
        return response()->json(['error' => $validator->errors()], 400);
    }

    $id_estandar = $request->input('id_estandar');
    $id_sede = $request->input('id_sede');
    $descripcion = $request->input('descripcion');
    $usuario = $request->user()->name;
    $ip = $request->ip();
    $nombreequipo = gethostbyaddr($ip);
    $fecha = now();
    try {
        DB::table('cpu_elemento_fundamental')->where('id', $id)->update([
            'id_estandar' => $id_estandar,
            'id_sede' => $id_sede,
            'descripcion' => $descripcion,
        ]);

        DB::table('cpu_auditoria')->insert([
            'aud_user' => $usuario,
            'aud_tabla' => 'cpu_elemento_fundamental',
            'aud_campo' => 'descripcion',
            'aud_dataold' => '',
            'aud_datanew' => "$id_estandar,$descripcion",
            'aud_tipo' => 'MODIFICACIÓN',
            'aud_fecha' => $fecha,
            'aud_ip' => $ip,
            'aud_tipoauditoria' => 2,
            'aud_descripcion' => "MODIFICACIÓN DE OBJETIVO $id_estandar,$descripcion,$id_sede",
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
    $descripcion = DB::table('cpu_elemento_fundamental')->where('id', $id)->value('descripcion');
    $usuario = $request->user()->name;
    $ip = $request->ip();
    $nombreequipo = gethostbyaddr($ip);
    $fecha = now();

    DB::table('cpu_elemento_fundamental')->where('id', $id)->delete();

    DB::table('cpu_auditoria')->insert([
        'aud_user' => $usuario,
        'aud_tabla' => 'cpu_elemento_fundamental',
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

public function consultarFuenteInformacionsede($id_sede)
    {
        $objetivos = DB::table('cpu_elemento_fundamental')
                        ->where('id_sede', $id_sede)
                        ->get();
        return response()->json($objetivos);
    }

}
