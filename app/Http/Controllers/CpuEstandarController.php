<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CpuEstandar;

class CpuEstandarController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Este método podría ser utilizado para devolver todos los registros, si es necesario
        $estandares = CpuEstandar::all();
        return response()->json($estandares);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'id_indicador' => 'required|exists:cpu_indicador,id',
            'descripcion' => 'required|string',
        ]);

        $estandar = new CpuEstandar();
        $estandar->id_indicador = $request->input('id_indicador');
        $estandar->descripcion = $request->input('descripcion');
        $estandar->save();

        return response()->json([
            'message' => 'Estandar creado exitosamente',
            'estandar' => $estandar
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    /**
     * Obtener los estándares por año e indicador.
     */
    public function obtenerEstandares($id_year, $id_indicador)
    {
        $estandares = CpuEstandar::whereHas('indicador', function($query) use ($id_year, $id_indicador) {
            $query->where('id_year', $id_year)
                  ->where('id', $id_indicador);
        })->get();

        return response()->json($estandares);
    }

}
