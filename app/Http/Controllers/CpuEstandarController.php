<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CpuEstandar;
use App\Models\CpuIndicador;

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
        //
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
    public function obtenerEstandares(Request $request)
    {
        $id_year = $request->input('id_year');
        $id_indicador = $request->input('id_indicador');

        $estandares = CpuEstandar::whereHas('indicador', function($query) use ($id_year, $id_indicador) {
            $query->where('id_year', $id_year)
                  ->where('id', $id_indicador);
        })->get();

        return response()->json($estandares);
    }
}
