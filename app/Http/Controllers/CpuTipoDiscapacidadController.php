<?php

namespace App\Http\Controllers;

use App\Models\CpuTipoDiscapacidad;
use Illuminate\Http\Request;

class CpuTipoDiscapacidadController extends Controller
{
    public function index()
    {
        return CpuTipoDiscapacidad::all();
    }

    public function store(Request $request)
    {
        $request->validate([
            'descripcion' => 'required|string|max:255',
        ]);

        return CpuTipoDiscapacidad::create($request->all());
    }

    public function show(CpuTipoDiscapacidad $cpuTipoDiscapacidad)
    {
        return $cpuTipoDiscapacidad;
    }

    public function update(Request $request, CpuTipoDiscapacidad $cpuTipoDiscapacidad)
    {
        $request->validate([
            'descripcion' => 'required|string|max:255',
        ]);

        $cpuTipoDiscapacidad->update($request->all());

        return $cpuTipoDiscapacidad;
    }

    public function destroy(CpuTipoDiscapacidad $cpuTipoDiscapacidad)
    {
        $cpuTipoDiscapacidad->delete();

        return response()->noContent();
    }
}
