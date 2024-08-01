<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CpuDatosSociales;
use App\Models\Persona;
use Illuminate\Support\Facades\Storage;

class CpuDatosSocialesController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'id_persona' => 'required|exists:cpu_personas,id',
            'persona' => 'required|json',
            'diagnostico' => 'nullable|string',
            'parentesco' => 'nullable|string',
            'problema_salud' => 'nullable|boolean',
            'markers' => 'nullable|json',
            'image' => 'nullable|image|max:2048'
        ]);

        $data = $request->only(['id_persona', 'persona', 'diagnostico', 'parentesco', 'problema_salud', 'markers']);

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('genogramas', 'public');
            $data['image_path'] = $path;
        }

        $datosSociales = DatosSociales::create($data);

        return response()->json($datosSociales, 201);
    }
}
