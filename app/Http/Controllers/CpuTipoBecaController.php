<?php

namespace App\Http\Controllers;

use App\Models\CpuTipoBeca;
use Illuminate\Http\Request;

class CpuTipoBecaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $tiposBeca = CpuTipoBeca::where('id_estado', 8)->get();
        return response()->json($tiposBeca);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $validatedData = $request->validate([
            'nombre_beca' => 'required|string',
            'nivel' => 'required|string',
            'id_estado' => 'nullable|integer'
        ]);

        $tipoBeca = CpuTipoBeca::create([
            'nombre_beca' => $validatedData['nombre_beca'],
            'nivel' => $validatedData['nivel'],
            'id_estado' => $validatedData['id_estado'] ?? 8,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return response()->json($tipoBeca, 201);
    }

    /**
     * Store a newly created resource in storage.
     */
    // public function store(Request $request)
    // {
    //     $tiposBeca = CpuTipoBeca::where('id_estado', 8)->get();
    //     return response()->json($tiposBeca);
    // }

    /**
     * Display the specified resource.
     */
    public function show()
    {
        $tiposBeca = CpuTipoBeca::where('id_estado', 8)->get();
        return response()->json($tiposBeca);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CpuTipoBeca $cpuTipoBeca)
    {
        //cambiar el estado a  8 o 9 dependiendo de lo qye tenga en la base de datos
        $tipoBeca = CpuTipoBeca::find($cpuTipoBeca->id);
        $tipoBeca->id_estado = $tipoBeca->id_estado == 8 ? 9 : 8;
        $tipoBeca->save();
        return response()->json($tipoBeca);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, CpuTipoBeca $cpuTipoBeca)
    {
        //actualizar uno o varios campos de la tabla cpu_tipo_beca
        $tipoBeca = CpuTipoBeca::find($cpuTipoBeca->id);
        $tipoBeca->update($request->all());
        return response()->json($tipoBeca);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CpuTipoBeca $cpuTipoBeca)
    {
        $tipoBeca = CpuTipoBeca::find($cpuTipoBeca->id);
        $tipoBeca->delete();
        return response()->json($tipoBeca);
    }
}
