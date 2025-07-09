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
        $this->auditoriaController = new AuditoriaControllers();
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
            'ca_id_usuario' => $data['id_usuario'],
            'ca_updated_at' => now(),
            'ca_parametros' => '',
            'ca_id_estado' => $data['select-estado'],
        ]);

        $id = DB::table('cpu_categorias_activos')->latest('ca_id')->first()->ca_id;

        $descripcionAuditoria = 'Se guardo la categoria de activos: ' . $data['txt-descripcion'] . ' con ID: ' . $id;
        $this->auditoriaController->auditar('cpu_categorias_activos', 'guardarCategoriaActivos()', '', json_encode($data), 'INSERT', $descripcionAuditoria);   

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

        DB::table('cpu_categorias_activos')
        ->where('ca_id', $id)  
        ->update([              
            'ca_descripcion' => $data['txt-descripcion'],
            'ca_id_usuario' => $data['id_usuario'],
            'ca_updated_at' => now(),
            'ca_id_estado' => $data['select-estado'],
        ]);

        $descripcionAuditoria = 'Se modifico la categoria de activos: ' . $data['txt-descripcion'] . ' con ID: ' . $id;
        $this->auditoriaController->auditar('cpu_categorias_activos', 'modificarCategoriaActivos()', '', json_encode($data), 'UPDATE', $descripcionAuditoria);
        return response()->json(['success' => true, 'message' => 'Categoria de Activos modificado correctamente']);
    }
}
