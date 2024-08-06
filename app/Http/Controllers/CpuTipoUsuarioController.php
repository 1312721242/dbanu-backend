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
}