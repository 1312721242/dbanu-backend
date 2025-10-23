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
        $this->auditoriaController = new AuditoriaControllers();
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
            'prov_id_usuario' => $data['id_usuario'],
            'created_at' => now(),
            'updated_at' => now(),
            'prov_estado' => $data['select-estado']
        ]);

        $id = DB::table('cpu_proveedores')->latest('prov_id')->first()->prov_id;
        
        $descripcionAuditoria = 'Se guardo el proveedor: ' . $data['txt-nombre'] . ' con RUC: ' . $data['txt-ruc']. ' y ID: ' . $id;
        $this->auditoriaController->auditar('cpu_proveedores', 'guardarProveedores()', '',json_encode($data), 'INSERT', $descripcionAuditoria);

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
            'prov_id_usuario' => $data['id_usuario'],
            'updated_at' => now(),
            'prov_estado' => $data['select-estado']
        ]);

        $descripcionAuditoria = 'Se modifico el proveedor: ' . $data['txt-nombre'] . ' con RUC: ' . $data['txt-ruc']. ' y ID: ' . $id;
        $this->auditoriaController->auditar('cpu_proveedores', 'modificarProveedores()', '',json_encode($data), 'UPDATE', $descripcionAuditoria);;

        return response()->json(['success' => true, 'message' => 'Proveedor modificado correctamente']);
    }

}
