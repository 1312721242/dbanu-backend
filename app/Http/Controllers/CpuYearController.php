<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Models\CpuYear;

class CpuYearController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }
    public function agregarYear(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'descripcion' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $ano = $request->input('descripcion');
        $usuario = $request->user()->name;
        $ip = $request->ip();
        $nombreequipo = gethostbyaddr($ip);
        $fecha = now();
        try {
            $year = CpuYear::create([
                'descripcion' => $ano,
            ]);
    
            DB::table('cpu_auditoria')->insert([
                'aud_user' => $usuario,
                'aud_tabla' => 'cpu_year',
                'aud_campo' => 'descripcion',
                'aud_dataold' => '',
                'aud_datanew' => $ano,
                'aud_tipo' => 'INSERCIÓN',
                'aud_fecha' => $fecha,
                'aud_ip' => $ip,
                'aud_tipoauditoria' => 1,
                'aud_descripcion' => "CREACION DE AÑO $ano",
                'aud_nombreequipo' => $nombreequipo,
                'created_at' =>$fecha,
                'updated_at' =>$fecha,
            ]);
    
            return response()->json(['success' => true, 'message' => 'Año agregada correctamente']);
        } catch (\Throwable $th) {
            return response()->json(['warning' => true, 'message' => 'El año que intentas registrar ya existe']);
        }
       
    }

    public function modificarYear(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'descripcion' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $ano = $request->input('descripcion');
        $usuario = $request->user()->name;
        $ip = $request->ip();
        $nombreequipo = gethostbyaddr($ip);
        $fecha = now();
        try {
            DB::table('cpu_years')->where('id', $id)->update([
                'descripcion' => $ano,
            ]);
    
            DB::table('cpu_auditoria')->insert([
                'aud_user' => $usuario,
                'aud_tabla' => 'cpu_year',
                'aud_campo' => 'descripcion',
                'aud_dataold' => '',
                'aud_datanew' => $ano,
                'aud_tipo' => 'MODIFICACIÓN',
                'aud_fecha' => $fecha,
                'aud_ip' => $ip,
                'aud_tipoauditoria' => 2,
                'aud_descripcion' => "MODIFICACIÓN DE AÑO $ano",
                'aud_nombreequipo' => $nombreequipo,
                'created_at' =>$fecha,
                'updated_at' =>$fecha,
            ]);
    
            return response()->json(['success' => true, 'message' => 'Año modificada correctamente']);
        } catch (\Throwable $th) {
            return response()->json(['warning' => true, 'message' => 'Ya existe un registro con este año por lo que la modificación que intentas hacer ha sido abortada']);
        }
       
    }

    public function eliminarYear(Request $request, $id)
    {
        $ano = DB::table('cpu_years')->where('id', $id)->value('descripcion');
        $usuario = $request->user()->name;
        $ip = $request->ip();
        $nombreequipo = gethostbyaddr($ip);
        $fecha = now();

        DB::table('cpu_years')->where('id', $id)->delete();

        DB::table('cpu_auditoria')->insert([
            'aud_user' => $usuario,
            'aud_tabla' => 'cpu_years',
            'aud_campo' => 'descripcion',
            'aud_dataold' => $ano,
            'aud_datanew' => '',
            'aud_tipo' => 'ELIMINACIÓN',
            'aud_fecha' => $fecha,
            'aud_ip' => $ip,
            'aud_tipoauditoria' => 3,
            'aud_descripcion' => "ELIMINACIÓN DE AÑO $ano",
            'aud_nombreequipo' => $nombreequipo,
            'created_at' =>$fecha,
            'updated_at' =>$fecha,
        ]);

        return response()->json(['success' => true, 'message' => 'Año eliminado correctamente']);
    }

    public function consultarYear(){
    
        $ano = DB::table('cpu_years')->get();

        return response()->json($ano);
    }

}
