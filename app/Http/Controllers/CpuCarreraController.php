<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\CpuCarrera;

class CpuCarreraController extends Controller
{
    public function agregarCarrera(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_sede' => 'required|integer',
            'id_facultad' => 'required|integer',
            'car_nombre' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $idSede = $request->input('id_sede');
        $idFacultad = $request->input('id_facultad');
        $nombreCarrera = $request->input('car_nombre');
        $usuario = $request->user()->name;
        $ip = $request->ip();
        $nombreequipo = gethostbyaddr($ip);
        $fecha = now();

        $carrera = CpuCarrera::create([
            'id_sede' => $idSede,
            'id_facultad' => $idFacultad,
            'car_nombre' => $nombreCarrera,
        ]);

        DB::table('cpu_auditoria')->insert([
            'aud_user' => $usuario,
            'aud_tabla' => 'cpu_carrera',
            'aud_campo' => 'car_nombre',
            'aud_dataold' => '',
            'aud_datanew' => $nombreCarrera,
            'aud_tipo' => 'INSERCION',
            'aud_fecha' => $fecha,
            'aud_ip' => $ip,
            'aud_tipoauditoria' => 1,
            'aud_descripcion' => "CREACION DE CARRERA $nombreCarrera",
            'aud_nombreequipo' => $nombreequipo,
        ]);

        return response()->json(['success' => true, 'message' => 'Carrera agregada correctamente']);
    }

    public function modificarCarrera(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'id_sede' => 'required|integer',
            'id_facultad' => 'required|integer',
            'car_nombre' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $idSede = $request->input('id_sede');
        $idFacultad = $request->input('id_facultad');
        $nombreCarrera = $request->input('car_nombre');
        $usuario = $request->user()->name;
        $ip = $request->ip();
        $nombreequipo = gethostbyaddr($ip);
        $fecha = now();

        // Actualizar la carrera en la tabla cpu_carrera
        DB::table('cpu_carrera')->where('id', $id)->update([
            'id_sede' => $idSede,
            'id_facultad' => $idFacultad,
            'car_nombre' => $nombreCarrera,
        ]);

        // Insertar en la tabla cpu_auditoria
        DB::table('cpu_auditoria')->insert([
            'aud_user' => $usuario,
            'aud_tabla' => 'cpu_carrera',
            'aud_campo' => 'car_nombre',
            'aud_dataold' => '',
            'aud_datanew' => $nombreCarrera,
            'aud_tipo' => 'MODIFICACION',
            'aud_fecha' => $fecha,
            'aud_ip' => $ip,
            'aud_tipoauditoria' => 2,
            'aud_descripcion' => "MODIFICACION DE CARRERA $nombreCarrera",
            'aud_nombreequipo' => $nombreequipo,
        ]);

        return response()->json(['success' => true, 'message' => 'Carrera modificada correctamente']);
    }

    public function eliminarCarrera(Request $request, $id)
    {
        $nombreCarrera = DB::table('cpu_carrera')->where('id', $id)->value('car_nombre');
        $usuario = $request->user()->name;
        $ip = $request->ip();
        $nombreequipo = gethostbyaddr($ip);
        $fecha = now();

        // Eliminar la carrera de la tabla cpu_carrera
        DB::table('cpu_carrera')->where('id', $id)->delete();

        // Insertar en la tabla cpu_auditoria
        DB::table('cpu_auditoria')->insert([
            'aud_user' => $usuario,
            'aud_tabla' => 'cpu_carrera',
            'aud_campo' => 'car_nombre',
            'aud_dataold' => $nombreCarrera,
            'aud_datanew' => '',
            'aud_tipo' => 'ELIMINACION',
            'aud_fecha' => $fecha,
            'aud_ip' => $ip,
            'aud_tipoauditoria' => 3,
            'aud_descripcion' => "ELIMINACION DE CARRERA $nombreCarrera",
            'aud_nombreequipo' => $nombreequipo,
        ]);

        return response()->json(['success' => true, 'message' => 'Carrera eliminada correctamente']);
    }

    public function consultarCarreras()
    {
        $carreras = DB::table('cpu_carrera')->get();

        return response()->json($carreras);
    }
}
