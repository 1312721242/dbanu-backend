<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CpuDatosSociales;
use Illuminate\Container\Attributes\Storage;

class CpuDatosSocialesController extends Controller
{

    // funcion para consultar datos sociales por id_persona
    public function show($id_persona)
{
    $datosSociales = CpuDatosSociales::where('id_persona', $id_persona)->first();

    if ($datosSociales) {
        $datosSociales->estructura_estudiante = json_decode($datosSociales->estructura_estudiante, true);
        $datosSociales->servicios_basicos_estudiante = json_decode($datosSociales->servicios_basicos_estudiante, true);
        $datosSociales->estructura_familia = json_decode($datosSociales->estructura_familia, true);
        $datosSociales->servicios_basicos_familia = json_decode($datosSociales->servicios_basicos_familia, true);
        $datosSociales->ingresos = json_decode($datosSociales->ingresos, true);
        $datosSociales->egresos = json_decode($datosSociales->egresos, true);
        $datosSociales->markers = json_decode($datosSociales->markers, true);
    }

    return response()->json($datosSociales);
}

    public function store(Request $request)
    {
        // dd($request);
        $validated = $request->validate([

            'id_persona' => 'required|integer', // Verifica que el id_persona sea un entero válido y exista en la tabla
            'situacion_estudiante' => 'nullable|string',
            'dormitorios_estudiante' => 'nullable|integer',
            'tipo_vivienda_estudiante' => 'nullable|string',
            'estructura_estudiante' => 'nullable|json',
            'servicios_basicos_estudiante' => 'nullable|json',
            'situacion_familia' => 'nullable|string',
            'dormitorios_familia' => 'nullable|integer',
            'tipo_vivienda_familia' => 'nullable|string',
            'estructura_familia' => 'nullable|json',
            'servicios_basicos_familia' => 'nullable|json',
            'problema_salud' => 'nullable|string',
            'diagnostico' => 'nullable|string',
            'parentesco' => 'nullable|string',
            'ingresos' => 'nullable|json',
            'egresos' => 'nullable|json',
            'diferencia' => 'nullable|numeric',
            'markers' => 'nullable|json',
            // 'image' => 'nullable|file|image|max:2048',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('genogramas', 'public');
        }

        $datosSociales = CpuDatosSociales::create(array_merge($validated, ['image_path' => $imagePath]));

        return response()->json(['message' => 'Datos sociales guardados exitosamente', 'data' => $datosSociales]);
    }

    public function updateByPersonaId(Request $request)
    {
        $validated = $request->validate([
            'id_persona' => 'required|integer',
            'situacion_estudiante' => 'nullable|string',
            'dormitorios_estudiante' => 'nullable|integer',
            'tipo_vivienda_estudiante' => 'nullable|string',
            'estructura_estudiante' => 'nullable|json',
            'servicios_basicos_estudiante' => 'nullable|json',
            'situacion_familia' => 'nullable|string',
            'dormitorios_familia' => 'nullable|integer',
            'tipo_vivienda_familia' => 'nullable|string',
            'estructura_familia' => 'nullable|json',
            'servicios_basicos_familia' => 'nullable|json',
            'problema_salud' => 'nullable|boolean',
            'diagnostico' => 'nullable|string',
            'parentesco' => 'nullable|string',
            'ingresos' => 'nullable|json',
            'egresos' => 'nullable|json',
            'diferencia' => 'nullable|numeric',
            'markers' => 'nullable|json',
            'image' => 'nullable|file|image|max:2048',
        ]);

        $datosSociales = CpuDatosSociales::where('id_persona', $validated['id_persona'])->firstOrFail();

        if ($request->hasFile('image')) {
            if ($datosSociales->image_path) {
                Storage::delete($datosSociales->image_path);
            }
            $validated['image_path'] = $request->file('image')->store('genogramas', 'public');
        }

        $datosSociales->update($validated);

        return response()->json(['message' => 'Datos sociales actualizados exitosamente', 'data' => $datosSociales]);
    }
}
