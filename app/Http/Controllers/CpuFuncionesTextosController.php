<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CpuFuncionesTextos;

class CpuFuncionesTextosController extends Controller
{
    public function index()
    {
        $funcionesTextos = CpuFuncionesTextos::all();
        return response()->json($funcionesTextos);
    }

    public function store(Request $request)
    {
        $funcionTexto = new CpuFuncionesTextos();
        $funcionTexto->descripcion = $request->descripcion;
        $funcionTexto->save();

        return response()->json(['message' => 'Función de texto creada', 'funcionTexto' => $funcionTexto]);
    }

    public function show($id)
    {
        $funcionTexto = CpuFuncionesTextos::find($id);
        return response()->json($funcionTexto);
    }

    public function update(Request $request, $id)
    {
        $funcionTexto = CpuFuncionesTextos::find($id);
        $funcionTexto->descripcion = $request->descripcion;
        $funcionTexto->save();

        return response()->json(['message' => 'Función de texto actualizada', 'funcionTexto' => $funcionTexto]);
    }

    public function destroy($id)
    {
        $funcionTexto = CpuFuncionesTextos::find($id);
        $funcionTexto->delete();

        return response()->json(['message' => 'Función de texto eliminada']);
    }
}
