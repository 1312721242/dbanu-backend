<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;


class ProductosControllers extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function consultarProductos()
    {
        $data = DB::select('SELECT * FROM public.view_productos');
        return response()->json($data);
    }

    public function saveProductos(Request $request)
    {
        log::info('data', $request->all());
        $data = $request->all();

        $validator = Validator::make($request->all(), [
            'txt-descripcion' => 'required|string|max:500',
            'txt-codigo' => 'required|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }
        $id = DB::table('cpu_productos')->insert([
            'pro_id_categoria' => $data['select-tipo'],
            'pro_descripcion' => $data['txt-descripcion'],
            'pro_codigo' => $data['txt-codigo'],
            'pro_estado' => $data['select-estado'],
            'pro_tipo' => $data['select-tipo'],
            'pro_unidad_medida' => $data['select-unidad-medida']
        ]);

        $id = DB::table('cpu_productos')->latest('pro_id')->first()->pro_id;

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

     public function modificarProductos(Request $request, $id)
    {
        log::info('data', $request->all()); 
        $data = $request->all();
        $validator = Validator::make($request->all(), [
             'txt-descripcion' => 'required|string|max:500',
            'txt-codigo' => 'required|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $facultadNombre = $request->input('fac_nombre');
        $usuario = $request->user()->name;
        $ip = $request->ip();
        $nombreequipo = gethostbyaddr($ip);
        $fecha = now();

        DB::table('cpu_productos')
        ->where('pro_id', $id)  
        ->update([              
            'pro_id_categoria' => $data['select-tipo'],
            'pro_descripcion' => $data['txt-descripcion'],
            'pro_codigo' => $data['txt-codigo'],
            'pro_estado' => $data['select-estado'],
            'pro_tipo' => $data['select-tipo'],
            'pro_unidad_medida' => $data['select-unidad-medida']
        ]);

        /*DB::table('cpu_auditoria')->insert([
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
        ]);*/

        return response()->json(['success' => true, 'message' => 'Producto modificado correctamente']);
    }

    
}
