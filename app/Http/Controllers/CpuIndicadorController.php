<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Models\cpu_indicador;

class CpuIndicadorController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }
    public function agregarIndicador(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'descripcion' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $indicador = $request->input('descripcion');
        $usuario = $request->user()->name;
        $ip = $request->ip();
        $nombreequipo = gethostbyaddr($ip);
        $fecha = now();
        try {
            $Indicador = CpuIndicador::create([
                'descripcion' => $indicador,
            ]);
    
            DB::table('cpu_auditoria')->insert([
                'aud_user' => $usuario,
                'aud_tabla' => 'cpu_indicador',
                'aud_campo' => 'descripcion',
                'aud_dataold' => '',
                'aud_datanew' => $indicador,
                'aud_tipo' => 'INSERCIÓN',
                'aud_fecha' => $fecha,
                'aud_ip' => $ip,
                'aud_tipoauditoria' => 1,
                'aud_descripcion' => "CREACION DE INDICADOR $indicador",
                'aud_nombreequipo' => $nombreequipo,
                'created_at' =>$fecha,
                'updated_at' =>$fecha,
            ]);
    
            return response()->json(['success' => true, 'message' => 'Indicador se agregada correctamente']);
        } catch (\Throwable $th) {
            return response()->json(['warning' => true, 'message' => 'El Indicador que intentas registrar ya existe']);
        }
       
    }

    public function modificarIndicador(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'descripcion' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $indicador = $request->input('descripcion');
        $usuario = $request->user()->name;
        $ip = $request->ip();
        $nombreequipo = gethostbyaddr($ip);
        $fecha = now();
        try {
            DB::table('cpu_indicador')->where('id', $id)->update([
                'descripcion' => $indicador,
            ]);
    
            DB::table('cpu_auditoria')->insert([
                'aud_user' => $usuario,
                'aud_tabla' => 'cpu_indicador',
                'aud_campo' => 'descripcion',
                'aud_dataold' => '',
                'aud_datanew' => $indicador,
                'aud_tipo' => 'MODIFICACIÓN',
                'aud_fecha' => $fecha,
                'aud_ip' => $ip,
                'aud_tipoauditoria' => 2,
                'aud_descripcion' => "MODIFICACIÓN DE AÑO $indicador",
                'aud_nombreequipo' => $nombreequipo,
                'created_at' =>$fecha,
                'updated_at' =>$fecha,
            ]);
    
            return response()->json(['success' => true, 'message' => 'Indicador modificado correctamente']);
        } catch (\Throwable $th) {
            return response()->json(['warning' => true, 'message' => 'Ya existe un registro con este Indicador por lo que la modificación que intentas hacer ha sido abortada']);
        }
       
    }

    public function eliminarInidcador(Request $request, $id)
    {
        $indicador = DB::table('cpu_indicador')->where('id', $id)->value('descripcion');
        $usuario = $request->user()->name;
        $ip = $request->ip();
        $nombreequipo = gethostbyaddr($ip);
        $fecha = now();

        DB::table('cpu_indicador')->where('id', $id)->delete();

        DB::table('cpu_auditoria')->insert([
            'aud_user' => $usuario,
            'aud_tabla' => 'cpu_indicador',
            'aud_campo' => 'descripcion',
            'aud_dataold' => $indicador,
            'aud_datanew' => '',
            'aud_tipo' => 'ELIMINACIÓN',
            'aud_fecha' => $fecha,
            'aud_ip' => $ip,
            'aud_tipoauditoria' => 3,
            'aud_descripcion' => "ELIMINACIÓN DE AÑO $indicador",
            'aud_nombreequipo' => $nombreequipo,
            'created_at' =>$fecha,
            'updated_at' =>$fecha,
        ]);

        return response()->json(['success' => true, 'message' => 'Año eliminado correctamente']);
    }

    public function consultarIndicador(){
    
        $indicador = DB::table('cpu_indicador')->get();

        return response()->json($indicador);
    }

}
