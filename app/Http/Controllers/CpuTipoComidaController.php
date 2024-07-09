<?php

namespace App\Http\Controllers;

use App\Models\CpuTipoComida;
use Illuminate\Http\Request;

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
        $request->validate([
            'descripcion' => 'required|string|max:255',
        ]);

        $cpuTipoComida = CpuTipoComida::find($id);

        if (is_null($cpuTipoComida)) {
            return response()->json(['message' => 'Record not found'], 404);
        }

        $cpuTipoComida->update($request->all());

        return response()->json($cpuTipoComida);
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
