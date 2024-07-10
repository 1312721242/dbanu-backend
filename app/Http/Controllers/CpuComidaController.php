<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CpuComida;

class CpuComidaController extends Controller
{
    public function index()
    {
        $comidas = CpuComida::with('tipoComida')->get()->map(function ($comida) {
            return [
                'id_comida' => $comida->id,
                'id_tipo_comida' => $comida->id_tipo_comida,
                'descripcion_comida' => $comida->descripcion,
                'descripcion_tipo_comida' => $comida->tipoComida->descripcion,
                'precio' => $comida->precio,
            ];
        });

        return response()->json($comidas);
    }

    public function indexTipoComida()
    {
        $comidas = CpuComida::with('tipoComida')->get();

        // Agrupar las comidas por tipo de comida
        $comidasAgrupadas = $comidas->groupBy('tipoComida.descripcion');

        // Formatear la respuesta
        $response = [];
        foreach ($comidasAgrupadas as $tipoComida => $comidas) {
            $comidasFormateadas = $comidas->map(function ($comida) {
                return [
                    'id_comida' => $comida->id,
                    'descripcion_comida' => $comida->descripcion,
                    'precio' => $comida->precio,
                ];
            })->toArray();

            $response[] = [
                'tipo_comida' => $tipoComida,
                'comidas' => $comidasFormateadas,
            ];
        }

        return response()->json($response);
    }


    public function store(Request $request)
    {
        $request->validate([
            'id_tipo_comida' => 'required|exists:cpu_tipo_comida,id',
            'descripcion' => 'required|string|max:255',
            'precio' => 'required|numeric',
        ]);

        $comida = CpuComida::create($request->all());

        return response()->json($comida, 201);
    }

    public function show($id)
    {
        $comida = CpuComida::findOrFail($id);
        return response()->json($comida);
    }

    public function update(Request $request, $id)
{
    $request->validate([
        'id_tipo_comida' => 'required|exists:cpu_tipo_comida,id',
        'descripcion' => 'required|string|max:255',
        'precio' => 'required|numeric',
    ]);

    $comida = CpuComida::findOrFail($id);
    $comida->update($request->all());

    return response()->json($comida, 200);
}

    public function destroy($id)
    {
        $comida = CpuComida::findOrFail($id);
        $comida->delete();

        return response()->json(null, 204);
    }
}
