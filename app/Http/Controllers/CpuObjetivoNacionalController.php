<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Models\CpuObjetivoNacional;

class CpuObjetivoNacionalController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }
    public function agregarObjetivoNacional(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_year' => 'required|integer',
            'descripcion' => 'required|string|unique:cpu_objetivo_nacional',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $ano = $request->input('id_year');
        $descripcion = $request->input('descripcion');
        $usuario = $request->user()->name;
        $ip = $request->ip();
        $nombreequipo = gethostbyaddr($ip);
        $fecha = now();
        try {
            $objetivo = CpuObjetivoNacional::create([
                'id_year' => $ano,
                'descripcion' => $descripcion,
            ]);
    
            DB::table('cpu_auditoria')->insert([
                'aud_user' => $usuario,
                'aud_tabla' => 'cpu_objetivo_nacional',
                'aud_campo' => 'descripcion',
                'aud_dataold' => '',
                'aud_datanew' => $ano,$descripcion,
                'aud_tipo' => 'INSERCIÓN',
                'aud_fecha' => $fecha,
                'aud_ip' => $ip,
                'aud_tipoauditoria' => 1,
                'aud_descripcion' => "CREACION DE OBJETIVO $ano,$descripcion",
                'aud_nombreequipo' => $nombreequipo,
                'created_at' =>$fecha,
                'updated_at' =>$fecha,
            ]);
    
            return response()->json(['success' => true, 'message' => 'Objetivo agregada correctamente']);
        } catch (\Throwable $th) {
            return response()->json(['warning' => true, 'message' => 'El Objetivo que intentas registrar ya existe']);
        }
       
    }

    public function modificarObjetivoNacional(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'id_year' => 'required|integer',
            'descripcion' => 'required|string|unique:cpu_estados',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $ano = $request->input('id_year');
        $descripcion = $request->input('descripcion');
        $usuario = $request->user()->name;
        $ip = $request->ip();
        $nombreequipo = gethostbyaddr($ip);
        $fecha = now();
        try {
            DB::table('cpu_objetivo_nacional')->where('id', $id)->update([
                'id_year' => $ano,
                'descripcion' => $descripcion,
            ]);
    
            DB::table('cpu_auditoria')->insert([
                'aud_user' => $usuario,
                'aud_tabla' => 'cpu_objetivo_nacional',
                'aud_campo' => 'descripcion',
                'aud_dataold' => '',
                'aud_datanew' => $ano,
                'aud_tipo' => 'MODIFICACIÓN',
                'aud_fecha' => $fecha,
                'aud_ip' => $ip,
                'aud_tipoauditoria' => 2,
                'aud_descripcion' => "MODIFICACIÓN DE OBJETIVO $ano,$descripcion",
                'aud_nombreequipo' => $nombreequipo,
                'created_at' =>$fecha,
                'updated_at' =>$fecha,
            ]);
    
            return response()->json(['success' => true, 'message' => 'Objetivo modificada correctamente']);
        } catch (\Throwable $th) {
            return response()->json(['warning' => true, 'message' => 'Ya existe un registro con este objetivo por lo que la modificación que intentas hacer ha sido abortada']);
        }
       
    }

    public function eliminarObjetivoNacional(Request $request, $id)
    {
        $descripcion = DB::table('cpu_objetivo_nacional')->where('id', $id)->value('descripcion');
        $usuario = $request->user()->name;
        $ip = $request->ip();
        $nombreequipo = gethostbyaddr($ip);
        $fecha = now();

        DB::table('cpu_objetivo_nacional')->where('id', $id)->delete();

        DB::table('cpu_auditoria')->insert([
            'aud_user' => $usuario,
            'aud_tabla' => 'cpu_objetivo_nacional',
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

    public function consultarObjetivoNacional(){
    
        $ano = DB::table('cpu_objetivo_nacional')->get();

        return response()->json($ano);
    }
}
