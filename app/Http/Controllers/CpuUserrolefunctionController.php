<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\Userrolefunction;

class CpuUserrolefunctionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function agregarFuncion(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_userrole' => 'required|integer',
            'nombre' => 'required|string|max:255',
            'accion' => 'required|string|max:255',
            'id_menu' => 'required|string|max:255',
            'id_usermenu' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $data = $request->only(['id_userrole', 'nombre', 'accion', 'id_menu', 'id_usermenu']);
        $data['created_at'] = now();
        $data['updated_at'] = now();

        $newUserfunction = Userrolefunction::create($data);

        $this->crearAuditoria($usuario = $request->user() ? $request->user()->name : 'Usuario no encontrado', 'cpu_userrolefunction', 'id_userrole, nombre, accion, id_menu, id_usermenu', '', 
        "{$data['id_userrole']}, {$data['nombre']}, {$data['accion']}, {$data['id_menu']}, {$data['id_usermenu']}", 'INSERCION');

        return response()->json(['success' => true, 'message' => 'Función agregada correctamente']);
    }

    public function modificarFuncion(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'id_userrole' => 'required|integer',
            'nombre' => 'required|string|max:255',
            'accion' => 'required|string|max:255',
            'id_menu' => 'required|string|max:255',
            'id_usermenu' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $data = $request->only(['id_userrole', 'nombre', 'accion', 'id_menu', 'id_usermenu']);
        $data['updated_at'] = now();

        Userrolefunction::where('id_userrf', $id)->update($data);

        $this->crearAuditoria($usuario = $request->user() ? $request->user()->name : 'Usuario no encontrado', 'cpu_userrolefunction', 'id_userrole, nombre, accion, id_menu, id_usermenu', '', 
        "{$data['id_userrole']}, {$data['nombre']}, {$data['accion']}, {$data['id_menu']}, {$data['id_usermenu']}", 'MODIFICACION');

        return response()->json(['success' => true, 'message' => 'Función modificada correctamente']);
    }

    public function eliminarFuncion(Request $request, $id)
    {
        $funcion = Userrolefunction::find($id);

        if (!$funcion) {
            return response()->json(['error' => 'La función no existe'], 404);
        }

        $funcion->delete();

        $this->crearAuditoria($usuario = $request->user() ? $request->user()->name : 'Usuario no encontrado', 'cpu_userrolefunction', 'id_userrole, nombre, accion, id_menu, id_usermenu', 
        "{$funcion->id_userrole}, {$funcion->nombre}, {$funcion->accion}, {$funcion->id_menu}, {$funcion->id_usermenu}", '', 'ELIMINACION');

        return response()->json(['success' => true, 'message' => 'Función eliminada correctamente']);
    }

    public function consultarFunciones()
    {
        $funciones = Userrolefunction::all();

        return response()->json($funciones);
    }

    private function crearAuditoria($usuario, $tabla, $campo, $dataold, $datanew, $tipo)
    {
        $ip = request()->ip();
        $nombreequipo = gethostbyaddr($ip);
        $fecha = now();

        DB::table('cpu_auditoria')->insert([
            'aud_user' => $usuario,
            'aud_tabla' => $tabla,
            'aud_campo' => $campo,
            'aud_dataold' => $dataold,
            'aud_datanew' => $datanew,
            'aud_tipo' => $tipo,
            'aud_fecha' => $fecha,
            'aud_ip' => $ip,
            'aud_tipoauditoria' => ($tipo == 'ELIMINACION') ? 3 : (($tipo == 'MODIFICACION') ? 2 : 1),
            'aud_descripcion' => strtoupper($tipo) . " DE FUNCION",
            'aud_nombreequipo' => $nombreequipo,
            'created_at' =>$fecha,
            'updated_at' =>$fecha,
        ]);
    }
}
