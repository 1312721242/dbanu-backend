<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class ProveedoresControllers extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function consultarProveedores()
    {
        $data = DB::select('SELECT * FROM public.view_proveedores');
        return response()->json($data);
    }

    public function guardarProveedores(Request $request)
    {
        log::info('data', $request->all());
        $data = $request->all();

        $validator = Validator::make($request->all(), [
            'txt-ruc' => 'required|string|max:500',
            'txt-nombre' => 'required|string|max:500',
            'txt-direccion' => 'required|string|max:500',
            'txt-telefono' => 'required|string|max:500',
            'txt-correo' => 'required|string|max:500',
            'select-estado' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }
        $id = DB::table('cpu_proveedores')->insert([
            'prov_ruc' => $data['txt-ruc'],
            'prov_nombre' => $data['txt-nombre'],
            'prov_direccion' => $data['txt-direccion'],
            'prov_telefono' => $data['txt-telefono'],
            'prov_correo' => $data['txt-correo'],
            'prov_id_usuario' => 1,
            'created_at' => now(),
            'updated_at' => now(),
            'prov_estado' => $data['select-estado']
        ]);

        $id = DB::table('cpu_proveedores')->latest('prov_id')->first()->prov_id;

        /*$fecha = now();
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

        ]);*/

        return response()->json(['success' => true, 'message' => 'Proveedor agregado correctamente']);
    }

    public function modificarProveedores(Request $request, $id)
    {
        log::info('data', $request->all()); 
        $data = $request->all();
        $validator = Validator::make($request->all(), [
            'txt-ruc' => 'required|string|max:500',
            'txt-nombre' => 'required|string|max:500',
            'txt-direccion' => 'required|string|max:500',
            'txt-telefono' => 'required|string|max:500',
            'txt-correo' => 'required|string|max:500',
            'select-estado' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $usuario = $request->user()->name;
        $ip = $request->ip();
        $nombreequipo = gethostbyaddr($ip);
        $fecha = now();
        $nombreAud_descripcion= $data['txt-nombre'];

        DB::table('cpu_proveedores')
        ->where('prov_id', $id)  
        ->update([              
            'prov_ruc' => $data['txt-ruc'],
            'prov_nombre' => $data['txt-nombre'],
            'prov_direccion' => $data['txt-direccion'],
            'prov_telefono' => $data['txt-telefono'],
            'prov_correo' => $data['txt-correo'],
            'prov_id_usuario' => 1,
            'updated_at' => now(),
            'prov_estado' => $data['select-estado']
        ]);

        /*DB::table('cpu_auditoria')->insert([
            'aud_user' => $usuario,
            'aud_tabla' => 'cpu_proveedores',
            'aud_campo' => 'prov_ruc,prov_nombre,prov_direccion,prov_telefono,prov_correo,prov_id_usuario,updated_at,prov_estado',
            'aud_dataold' => '',
            'aud_datanew' => $data,
            'aud_tipo' => 'MODIFICACION',
            'aud_fecha' => $fecha,
            'aud_ip' => $ip,
            'aud_tipoauditoria' => 2,
            'aud_descripcion' => "MODIFICACION DE PROVEEDOR $nombreAud_descripcion",
            'aud_nombreequipo' => $nombreequipo,
        ]);*/

        return response()->json(['success' => true, 'message' => 'Producto modificado correctamente']);
    }

}
