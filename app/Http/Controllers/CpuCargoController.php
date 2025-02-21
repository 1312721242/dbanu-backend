<?php

namespace App\Http\Controllers;

use App\Models\CpuCargo;
use Illuminate\Http\Request;

class CpuCargoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $cargo = CpuCargo::all();
        return response()->json($cargo);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        //CREAR UN NUEVO CARGO
        $cargo = CpuCargo::create($request->all());
        return response()->json($cargo);
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
    public function show(CpuCargo $cpuCargo)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CpuCargo $cpuCargo)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        //ACTUALIZAR UN CARGO
        $cargo = CpuCargo::find($request->id);
        $cargo->update($request->all());
        return response()->json($cargo);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request)
    {
        //ELIMINAR UN CARGO
        $cargo = CpuCargo::find($request->id);
        $cargo->delete();
        return response()->json($cargo);
    }




}
