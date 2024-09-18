<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\Role; // Importar el modelo Role

class RoleController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }
    public function agregarRoleUsuario(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'role' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $role = strtoupper($request->input('role'));
        $usuario = $request->user()->name; // Obtener el nombre de usuario autenticado
        $ip = $request->ip();
        $nombreequipo = gethostbyaddr($ip);
        $fecha = now();

        // Insertar en la tabla cpu_userrole
        $newRole = Role::create([
            'role' => $role,
        ]);

        // Insertar en la tabla cpu_auditoria
        DB::table('cpu_auditoria')->insert([
            'aud_user' => $usuario,
            'aud_tabla' => 'cpu_userrole',
            'aud_campo' => 'role',
            'aud_dataold' => '',
            'aud_datanew' => $role,
            'aud_tipo' => 'INSERCION',
            'aud_fecha' => $fecha,
            'aud_ip' => $ip,
            'aud_tipoauditoria' => 1,
            'aud_descripcion' => "CREACION DE ROLE DE $role",
            'aud_nombreequipo' => $nombreequipo,
        ]);

        return response()->json(['success' => true, 'message' => 'Role agregado correctamente']);
    }

    public function modificarRoleUsuario(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'role' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $role = strtoupper($request->input('role'));
        $usuario = $request->user()->name; // Obtener el nombre de usuario autenticado
        $ip = $request->ip();
        $nombreequipo = gethostbyaddr($ip);
        $fecha = now();

        // Actualizar el rol en la tabla cpu_userrole
        DB::table('cpu_userrole')->where('id_userrole', $id)->update([
            'role' => $role,
        ]);

        // Insertar en la tabla cpu_auditoria
        DB::table('cpu_auditoria')->insert([
            'aud_user' => $usuario,
            'aud_tabla' => 'cpu_userrole',
            'aud_campo' => 'role',
            'aud_dataold' => '',
            'aud_datanew' => $role,
            'aud_tipo' => 'MODIFICACION',
            'aud_fecha' => $fecha,
            'aud_ip' => $ip,
            'aud_tipoauditoria' => 2,
            'aud_descripcion' => "MODIFICACION DE ROLE DE $role",
            'aud_nombreequipo' => $nombreequipo,
        ]);

        return response()->json(['success' => true, 'message' => 'Role modificado correctamente']);
    }

    public function eliminarRoleUsuario(Request $request, $id)
    {
        $role = DB::table('cpu_userrole')->where('id_userrole', $id)->value('role');
        $usuario = $request->user()->name; // Obtener el nombre de usuario autenticado
        $ip = $request->ip();
        $nombreequipo = gethostbyaddr($ip);
        $fecha = now();

        // Eliminar el rol de la tabla cpu_userrole
        DB::table('cpu_userrole')->where('id_userrole', $id)->delete();

        // Insertar en la tabla cpu_auditoria
        DB::table('cpu_auditoria')->insert([
            'aud_user' => $usuario,
            'aud_tabla' => 'cpu_userrole',
            'aud_campo' => 'role',
            'aud_dataold' => $role,
            'aud_datanew' => '',
            'aud_tipo' => 'ELIMINACION',
            'aud_fecha' => $fecha,
            'aud_ip' => $ip,
            'aud_tipoauditoria' => 3,
            'aud_descripcion' => "ELIMINACION DE ROLE DE $role",
            'aud_nombreequipo' => $nombreequipo,
        ]);

        return response()->json(['success' => true, 'message' => 'Role eliminado correctamente']);
    }

    public function consultarRoles()
    {
        $roles = DB::table('cpu_userrole')->get();

        return response()->json($roles);
    }

    public function consultarAreas()
    {
        $roles = DB::table('cpu_userrole')
            ->whereIn('id_userrole', [7, 8, 9, 11, 13, 14,15])
            ->orderBy('role', 'asc')
            ->get();

        return response()->json($roles);
    }
}
