<?php

namespace App\Http\Controllers;

use App\Models\CpuTipoUsuario;
use Illuminate\Http\Request;

class CpuTipoUsuarioController extends Controller
{
    public function index()
    {
        return CpuTipoUsuario::all();
    }

    public function store(Request $request)
    {
        $request->validate([
            'tipo_usuario' => 'required|string|max:255',
        ]);

        return CpuTipoUsuario::create($request->all());
    }

    public function show($id)
    {
        return CpuTipoUsuario::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'tipo_usuario' => 'required|string|max:255',
        ]);

        $cpuTipoUsuario = CpuTipoUsuario::findOrFail($id);
        $cpuTipoUsuario->update($request->all());

        return $cpuTipoUsuario;
    }

    public function destroy($id)
    {
        $cpuTipoUsuario = CpuTipoUsuario::findOrFail($id);
        $cpuTipoUsuario->delete();

        return response()->noContent();
    }

    public function filtrotipousuario($tipo_usu)
    {
        // Determina la clasificación en función del tipo de usuario recibido
        $clasificacion = $tipo_usu === 'COMUNIDAD UNIVERSITARIA' ? 2 : 1;

        // Realiza la consulta en la base de datos
        $tiposUsuario = CpuTipoUsuario::where('clasificacion', $clasificacion)->get();

        // Retorna los resultados
        return response()->json($tiposUsuario);
    }
}