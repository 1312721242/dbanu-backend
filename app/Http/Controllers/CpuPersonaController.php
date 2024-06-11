<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CpuPersona;
use App\Models\CpuDatosEmpleado;
use Illuminate\Support\Facades\Http;

class CpuPersonaController extends Controller
{
    public function show($cedula)
    {
        if (strlen($cedula) < 10) {
            $personas = CpuPersona::where('cedula', 'like', "{$cedula}%")
                ->with('datosEmpleados')
                ->get();
            return response()->json($personas);
        }

        $persona = CpuPersona::where('cedula', $cedula)->with('datosEmpleados')->first();

        if ($persona) {
            return response()->json($persona);
        }

        $response = Http::get("https://apps2.uleam.edu.ec/DATHApi/api/personal/{$cedula}/bienestar");
        if ($response->successful()) {
            $data = $response->json();

            $persona = CpuPersona::create([
                'cedula' => $data['cedula'],
                'nombres' => $data['nombres'],
                'nacionalidad' => $data['nacionalidad'],
                'provincia' => $data['provincia'],
                'ciudad' => $data['ciudad'],
                'parroquia' => $data['parroquia'],
                'direccion' => $data['direccion'],
                'sexo' => $data['sexo'],
                'fechanaci' => $data['fechanaci'],
                'celular' => $data['celular'],
                'tipoetnia' => $data['tipoetnia'],
                'discapacidad' => $data['discapacidad'],
            ]);

            CpuDatosEmpleado::create([
                'id_persona' => $persona->id,
                'emailinstitucional' => $data['emailinstitucional'],
                'puesto' => $data['puesto'],
                'regimen1' => $data['regimen1'],
                'modalidad' => $data['modalidad'],
                'unidad' => $data['unidad'],
                'carrera' => $data['carrera'],
                'idsubproceso' => $data['idSubProceso'],
                'escala1' => $data['escala1'],
                'estado' => $data['estado'],
                'fechaingre' => $data['fechaIngre'],
            ]);

            $persona->load('datosEmpleados');
            return response()->json([$persona]);
        }

        return response()->json(['message' => 'Persona no encontrada'], 404);
    }

    public function update(Request $request, $cedula)
    {
        $persona = CpuPersona::where('cedula', $cedula)->first();
        if (!$persona) {
            return response()->json(['message' => 'Persona no encontrada'], 404);
        }

        $persona->update($request->only([
            'nombres', 'nacionalidad', 'provincia', 'ciudad', 'parroquia', 'direccion', 'sexo', 'fechanaci', 'celular', 'tipoetnia', 'discapacidad'
        ]));

        $persona->datosEmpleados()->update($request->only([
            'emailinstitucional', 'puesto', 'regimen1', 'modalidad', 'unidad', 'carrera', 'idsubproceso', 'escala1', 'estado', 'fechaingre'
        ]));

        return response()->json($persona->load('datosEmpleados'));
    }
}
