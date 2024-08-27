<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\Userfunction;

class CpuUserfunctionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }
    public function agregarFuncion(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'id_users' => 'required|integer',
            'id_usermenu' => 'required|integer',
            'id_userrole' => 'required|integer',
            'nombre' => 'required|string|max:255',
            'accion' => 'required|string|max:255',
            'id_menu' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $data = $request->only(['id_users', 'id_usermenu', 'id_userrole', 'nombre', 'accion', 'id_menu']);
        $data['created_at'] = now();
        $data['updated_at'] = now();

        $newUserfunction = Userfunction::create($data);

        $this->crearAuditoria($usuario = $request->user() ? $request->user()->name : 'Usuario no encontrado', 'cpu_userfunction', 'id_users, id_usermenu, id_userrole, nombre, accion, id_menu', '',
        "{$data['id_users']}, {$data['id_usermenu']}, {$data['id_userrole']}, {$data['nombre']}, {$data['accion']}, {$data['id_menu']}", 'INSERCION');

        return response()->json(['success' => true, 'message' => 'Función agregada correctamente']);
    }

    public function agregarFunciones(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'funciones' => 'required|array',
            'funciones.*.id_users' => 'required|integer',
            'funciones.*.id_usermenu' => 'required|integer',
            'funciones.*.id_userrole' => 'required|integer',
            'funciones.*.nombre' => 'required|string|max:255',
            'funciones.*.accion' => 'required|string|max:255',
            'funciones.*.id_menu' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $funciones = $request->input('funciones');
        foreach ($funciones as $funcion) {
            $funcion['created_at'] = now();
            $funcion['updated_at'] = now();

            Userfunction::create($funcion);

            $this->crearAuditoria(
                $usuario = $request->user() ? $request->user()->name : 'Usuario no encontrado',
                'cpu_userfunction',
                'id_users, id_usermenu, id_userrole, nombre, accion, id_menu',
                '',
                "{$funcion['id_users']}, {$funcion['id_usermenu']}, {$funcion['id_userrole']}, {$funcion['nombre']}, {$funcion['accion']}, {$funcion['id_menu']}",
                'INSERCION'
            );
        }

        return response()->json(['success' => true, 'message' => 'Funciones agregadas correctamente']);
    }


    public function modificarFuncion(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'id_users' => 'required|integer',
            'id_usermenu' => 'required|integer',
            'id_userrole' => 'required|integer',
            'nombre' => 'required|string|max:255',
            'accion' => 'required|string|max:255',
            'id_menu' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $data = $request->only(['id_users', 'id_usermenu', 'id_userrole', 'nombre', 'accion', 'id_menu']);
        $data['updated_at'] = now();

        Userfunction::where('id_userfunction', $id)->update($data);

        $this->crearAuditoria($usuario = $request->user() ? $request->user()->name : 'Usuario no encontrado', 'cpu_userfunction', 'id_users, id_usermenu, id_userrole, nombre, accion, id_menu', '',
        "{$data['id_users']}, {$data['id_usermenu']}, {$data['id_userrole']}, {$data['nombre']}, {$data['accion']}, {$data['id_menu']}", 'MODIFICACION');

        return response()->json(['success' => true, 'message' => 'Función modificada correctamente']);
    }

    public function eliminarFuncion(Request $request, $id)
    {
        $funcion = Userfunction::find($id);

        if (!$funcion) {
            return response()->json(['error' => 'La función no existe'], 404);
        }

        $funcion->delete();

        $this->crearAuditoria($usuario = $request->user() ? $request->user()->name : 'Usuario no encontrado', 'cpu_userfunction', 'id_users, id_usermenu, id_userrole, nombre, accion, id_menu',
        "{$funcion->id_users}, {$funcion->id_usermenu}, {$funcion->id_userrole}, {$funcion->nombre}, {$funcion->accion}, {$funcion->id_menu}", '', 'ELIMINACION');

        return response()->json(['success' => true, 'message' => 'Función eliminada correctamente']);
    }

    public function consultarFunciones()
    {
        $funciones = Userfunction::all();

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

    //funcion para eliminar las funciones asocoadas al usuario
    public function eliminarFuncionesUsuario($id)
        {
            try {
                Userfunction::where('id_users', $id)->delete();
                return response()->json(['success' => true, 'message' => 'Funciones eliminadas correctamente']);
            } catch (\Exception $e) {
                return response()->json(['error' => 'Error al eliminar las funciones'], 500);
            }
        }

}
