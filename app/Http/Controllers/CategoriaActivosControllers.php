<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
namespace App\Http\Controllers;
use Illuminate\Http\Request;
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class CategoriaActivosControllers extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function consultarCategoriaActivos()
    {
        $data = DB::select('SELECT * FROM public.view_categoria_activos');
        return response()->json($data);
    }

    public function guardarCategoriaActivos(Request $request)
    {
        log::info('data', $request->all());
        $data = $request->all();

        $validator = Validator::make($request->all(), [
            'txt-descripcion' => 'required|string|max:500',
            'select-estado' => 'required|integer'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }
        $id = DB::table('cpu_categorias_activos')->insert([
            'ca_descripcion' => $data['txt-descripcion'],
            'ca_created_at' => now(),
            'ca_id_usuario' => 1,
            'ca_updated_at' => now(),
            'ca_parametros' => '',
            'ca_id_estado' => $data['select-estado'],
        ]);

        $id = DB::table('cpu_categorias_activos')->latest('ca_id')->first()->ca_id;

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

    public function modificarCategoriaActivos(Request $request, $id)
    {
        log::info('data', $request->all()); 
        $data = $request->all();
        $validator = Validator::make($request->all(), [
            'txt-descripcion' => 'required|string|max:500',
            'select-estado' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        /*$usuario = $request->user()->name;
        $ip = $request->ip();
        $nombreequipo = gethostbyaddr($ip);
        $fecha = now();
        $nombreAud_descripcion= $data['txt-descripcion'];*/

        DB::table('cpu_categorias_activos')
        ->where('ca_id', $id)  
        ->update([              
            'ca_descripcion' => $data['txt-descripcion'],
            'ca_id_usuario' => 1,
            'ca_updated_at' => now(),
            'ca_id_estado' => $data['select-estado'],
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

        return response()->json(['success' => true, 'message' => 'Categoria de Activos modificado correctamente']);
    }
}
