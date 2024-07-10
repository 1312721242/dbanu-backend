<?php

namespace App\Http\Controllers;

use App\Models\CpuTipoComida;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CpuTipoComidaController extends Controller
{
    public function index()
    {
        return CpuTipoComida::all();
    }

    public function store(Request $request)
    {
        $request->validate([
            'descripcion' => 'required|string|max:255',
        ]);

        $cpuTipoComida = CpuTipoComida::create($request->all());

        return response()->json($cpuTipoComida, 201);
    }

    public function show($id)
    {
        $cpuTipoComida = CpuTipoComida::find($id);

        if (is_null($cpuTipoComida)) {
            return response()->json(['message' => 'Record not found'], 404);
        }

        return response()->json($cpuTipoComida);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'descripcion' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $descripcion = $request->input('descripcion');

        $tipoComidaActual = CpuTipoComida::find($id);
        if (!$tipoComidaActual) {
            return response()->json(['error' => 'Tipo de comida no encontrado'], 404);
        }

        // Guarda la descripción anterior antes de actualizar
        $descripcionAnterior = $tipoComidaActual->descripcion;

        // Actualiza el tipo de comida con la nueva descripción
        $tipoComidaActual->descripcion = $descripcion;
        $tipoComidaActual->save();

        // Auditoría
        $usuario = $request->user()->name;
        $ip = $request->ip();
        $nombreequipo = gethostbyaddr($ip);
        $fecha = now();

        DB::table('cpu_auditoria')->insert([
            'aud_user' => $usuario,
            'aud_tabla' => 'cpu_tipo_comida',
            'aud_campo' => 'descripcion',
            'aud_dataold' => $descripcionAnterior,
            'aud_datanew' => $descripcion,
            'aud_tipo' => 'MODIFICACION',
            'aud_fecha' => $fecha,
            'aud_ip' => $ip,
            'aud_tipoauditoria' => 2,
            'aud_descripcion' => "MODIFICACION DE DESCRIPCION $descripcion",
            'aud_nombreequipo' => $nombreequipo,
        ]);

        return response()->json(['success' => true, 'message' => 'Tipo de comida actualizado correctamente']);
    }



    public function destroy($id)
    {
        $cpuTipoComida = CpuTipoComida::find($id);

        if (is_null($cpuTipoComida)) {
            return response()->json(['message' => 'Record not found'], 404);
        }

        $cpuTipoComida->delete();

        return response()->json(null, 204);
    }
}
