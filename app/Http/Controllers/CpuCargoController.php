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
    public function update(Request $request, CpuCargo $cpuCargo)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CpuCargo $cpuCargo)
    {
        //
    }
}
