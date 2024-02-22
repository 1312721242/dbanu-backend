<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\CpuUsermenu;

class CpuUsermenuController extends Controller
{
    public function agregarMenu(Request $request)
    {
        // Buscar al usuario por el token enviado en el encabezado
    $usuario = $request->user('sanctum')->name;

    $validator = Validator::make($request->all(), [
        'menu' => 'required|string|max:255',
        'icono' => 'required|string|max:255',
    ]);

    if ($validator->fails()) {
        return response()->json(['error' => $validator->errors()], 400);
    }

    $menu = $request->input('menu');
    $icono = $request->input('icono');
    $ip = $request->ip();
    $nombreequipo = gethostbyaddr($ip);
    $fecha = now();

    $newUsermenu = CpuUsermenu::create([
        'menu' => $menu,
        'icono' => $icono,
    ]);

    DB::table('cpu_auditoria')->insert([
        'aud_user' => $usuario,
        'aud_tabla' => 'cpu_usermenu',
        'aud_campo' => 'menu, icono',
        'aud_dataold' => '',
        'aud_datanew' => "$menu, $icono",
        'aud_tipo' => 'INSERCION',
        'aud_fecha' => $fecha,
        'aud_ip' => $ip,
        'aud_tipoauditoria' => 1,
        'aud_descripcion' => "CREACION DE MENU $menu con icono $icono",
        'aud_nombreequipo' => $nombreequipo,
        'created_at' =>$fecha,
        'updated_at' =>$fecha,
    ]);

    return response()->json(['success' => true, 'message' => 'Menú agregado correctamente']);
    }

    public function modificarMenu(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'menu' => 'required|string|max:255',
            'icono' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $menu = $request->input('menu');
        $icono = $request->input('icono');
        $usuario = $request->user()->name;
        $ip = $request->ip();
        $nombreequipo = gethostbyaddr($ip);
        $fecha = now();

        DB::table('cpu_usermenu')->where('id_usermenu', $id)->update([
            'menu' => $menu,
            'icono' => $icono,
        ]);

        DB::table('cpu_auditoria')->insert([
            'aud_user' => $usuario,
            'aud_tabla' => 'cpu_usermenu',
            'aud_campo' => 'menu, icono',
            'aud_dataold' => '',
            'aud_datanew' => "$menu, $icono",
            'aud_tipo' => 'MODIFICACION',
            'aud_fecha' => $fecha,
            'aud_ip' => $ip,
            'aud_tipoauditoria' => 2,
            'aud_descripcion' => "MODIFICACION DE MENU $menu con icono $icono",
            'aud_nombreequipo' => $nombreequipo,
            'created_at' =>$fecha,
            'updated_at' =>$fecha,
        ]);

        return response()->json(['success' => true, 'message' => 'Menú modificado correctamente']);
    }

    public function eliminarMenu(Request $request, $id)
    {
        $menu = DB::table('cpu_usermenu')->where('id_usermenu', $id)->value('menu');
        $usuario = $request->user()->name;
        $ip = $request->ip();
        $nombreequipo = gethostbyaddr($ip);
        $fecha = now();

        DB::table('cpu_usermenu')->where('id_usermenu', $id)->delete();

        DB::table('cpu_auditoria')->insert([
            'aud_user' => $usuario,
            'aud_tabla' => 'cpu_usermenu',
            'aud_campo' => 'menu',
            'aud_dataold' => $menu,
            'aud_datanew' => '',
            'aud_tipo' => 'ELIMINACION',
            'aud_fecha' => $fecha,
            'aud_ip' => $ip,
            'aud_tipoauditoria' => 3,
            'aud_descripcion' => "ELIMINACION DE MENU $menu",
            'aud_nombreequipo' => $nombreequipo,
            'created_at' =>$fecha,
            'updated_at' =>$fecha,
        ]);

        return response()->json(['success' => true, 'message' => 'Menú eliminado correctamente']);
    }

    public function consultarMenus()
    {
        $menus = DB::table('cpu_usermenu')->get();

        return response()->json($menus);
    }
}
