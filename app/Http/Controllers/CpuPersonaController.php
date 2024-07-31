<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CpuPersona;
use App\Models\CpuDatosEmpleado;
use App\Models\CpuDatosMedicos;
use App\Models\CpuDatosEstudiantes;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class CpuPersonaController extends Controller
{
    //aqui para tenciones de medico ocupacional
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

    // public function update(Request $request, $cedula)
    // {
    //     $persona = CpuPersona::where('cedula', $cedula)->first();
    //     if (!$persona) {
    //         return response()->json(['message' => 'Persona no encontrada'], 404);
    //     }

    //     $persona->update($request->only([
    //         'nombres', 'nacionalidad', 'provincia', 'ciudad', 'parroquia', 'direccion', 'sexo', 'fechanaci', 'celular', 'tipoetnia', 'discapacidad'
    //     ]));

    //     $persona->datosEmpleados()->update($request->only([
    //         'emailinstitucional', 'puesto', 'regimen1', 'modalidad', 'unidad', 'carrera', 'idsubproceso', 'escala1', 'estado', 'fechaingre'
    //     ]));

    //     return response()->json($persona->load('datosEmpleados'));
    // }
    //aqui para atenciones de bienestar
    public function showBienestar($cedula)
    {
        if (strlen($cedula) < 10) {
            $personas = CpuPersona::where('cedula', 'like', "{$cedula}%")
                ->with(['datosEmpleados', 'datosEstudiantes'])
                ->get();
            return response()->json($personas);
        }

        $persona = CpuPersona::where('cedula', $cedula)->with(['datosEmpleados', 'datosEstudiantes'])->first();

        if ($persona) {
            $persona->load('datosMedicos'); // Load datosMedicos if available
            return response()->json($persona);
        }

        // First API call
        $response = Http::get("https://apps2.uleam.edu.ec/DATHApi/api/personal/{$cedula}/bienestar");
        if ($response->successful()) {
            $data = $response->json();

            // Check if the data returned is essentially empty
            $isEmptyData = empty($data['cedula']) && empty($data['nombres']) && empty($data['nacionalidad']) &&
                empty($data['provincia']) && empty($data['ciudad']) && empty($data['parroquia']) &&
                empty($data['direccion']) && empty($data['sexo']) && empty($data['fechanaci']) &&
                empty($data['celular']) && empty($data['tipoetnia']);

            if (!$isEmptyData) {
                $persona = CpuPersona::create([
                    'cedula' => $data['cedula'] ?? '',
                    'nombres' => $data['nombres'] ?? 'SIN INFORMACIÓN',
                    'nacionalidad' => $data['nacionalidad'] ?? 'SIN INFORMACIÓN',
                    'provincia' => $data['provincia'] ?? 'SIN INFORMACIÓN',
                    'ciudad' => $data['ciudad'] ?? 'SIN INFORMACIÓN',
                    'parroquia' => $data['parroquia'] ?? 'SIN INFORMACIÓN',
                    'direccion' => $data['direccion'] ?? 'SIN INFORMACIÓN',
                    'sexo' => $data['sexo'] ?? 'SIN INFORMACIÓN',
                    'fechanaci' => $data['fechanaci'] ?? '1900-01-01',
                    'celular' => $data['celular'] ?? 'SIN INFORMACIÓN',
                    'tipoetnia' => $data['tipoetnia'] ?? 'SIN INFORMACIÓN',
                    'discapacidad' => $data['discapacidad'] ?? 'SIN INFORMACIÓN',
                ]);

                CpuDatosEmpleado::create([
                    'id_persona' => $persona->id,
                    'emailinstitucional' => $data['emailinstitucional'] ?? 'SIN INFORMACIÓN',
                    'puesto' => $data['puesto'] ?? 'SIN INFORMACIÓN',
                    'regimen1' => $data['regimen1'] ?? 'SIN INFORMACIÓN',
                    'modalidad' => $data['modalidad'] ?? 'SIN INFORMACIÓN',
                    'unidad' => $data['unidad'] ?? 'SIN INFORMACIÓN',
                    'carrera' => $data['carrera'] ?? 'SIN INFORMACIÓN',
                    'idsubproceso' => $data['idSubProceso'] ?? null,
                    'escala1' => $data['escala1'] ?? 'SIN INFORMACIÓN',
                    'estado' => $data['estado'] ?? 'SIN INFORMACIÓN',
                    'fechaingre' => $data['fechaIngre'] ?? '1900-01-01',
                ]);

                $persona->load(['datosEmpleados', 'datosEstudiantes']); // Load datosEstudiantes if available
                return response()->json([$persona]);
            }
        }

        // Second API call if first API doesn't provide any data
        $response = Http::get("https://apps.uleam.edu.ec/SGAAPI/api/Estudiantes/{$cedula}/bienestar");
        if ($response->successful() && !empty($response->json())) {
            $data = $response->json();

            $discapacidad = $data['discapacidad'];
            if (!in_array($discapacidad, ['Sí', 'No', 'SI', 'NO'])) {
                $discapacidadData = DB::table('public.cpu_legalizacion_matricula')
                    ->where('cedula', $cedula)
                    ->first(['discapacidad']);
                if (!$discapacidadData) {
                    $discapacidadData = DB::table('public.cpu_mtn_2018_2022')
                        ->where('cedula', $cedula)
                        ->first(['discapacidad']);
                }
                $discapacidad = $discapacidadData ? $discapacidadData->discapacidad : $discapacidad;
            }

            $segmentacionPersona = $data['segmentacionPersona'];
            if (empty($segmentacionPersona) || $segmentacionPersona === 'SIN INFORMACIÓN') {
                $segmentacionData = DB::table('public.cpu_legalizacion_matricula')
                    ->where('cedula', $cedula)
                    ->first(['segmento_persona']);
                if (!$segmentacionData) {
                    $segmentacionData = DB::table('public.cpu_mtn_2018_2022')
                        ->where('cedula', $cedula)
                        ->first(['segmento']);
                }
                $segmentacionPersona = $segmentacionData ? $segmentacionData->segmento : $segmentacionPersona;
            }

            $etnia = $data['etnia'];
            if (empty($etnia) || in_array($etnia, ['NO REGISTRA', '', 'SIN INFORMACIÓN'])) {
                $etniaData = DB::table('public.cpu_legalizacion_matricula')
                    ->where('cedula', $cedula)
                    ->first(['etnia']);
                if (!$etniaData) {
                    $etniaData = DB::table('public.cpu_mtn_2018_2022')
                        ->where('cedula', $cedula)
                        ->first(['etnia']);
                }
                $etnia = $etniaData ? $etniaData->etnia : $etnia;
            }

            $persona = CpuPersona::create([
                'cedula' => $cedula,
                'nombres' => $data['nombres'] ?? 'SIN INFORMACIÓN',
                'nacionalidad' => $data['nacionalidad'] ?? 'SIN INFORMACIÓN',
                'provincia' => $data['provincia'] ?? 'SIN INFORMACIÓN',
                'ciudad' => $data['ciudad'] ?? 'SIN INFORMACIÓN',
                'parroquia' => 'NO REGISTRA',
                'direccion' => $data['direccionDomicilio'] ?? 'SIN INFORMACIÓN',
                'sexo' => $data['sexo'] ?? 'SIN INFORMACIÓN',
                'fechanaci' => $data['fechaNacimiento'] ?? '1900-01-01',
                'celular' => $data['celular'] ?? 'SIN INFORMACIÓN',
                'tipoetnia' => $etnia,
                'discapacidad' => $discapacidad,
            ]);

            CpuDatosEstudiantes::create([
                'id_persona' => $persona->id,
                'campus' => $data['campus'] ?? 'SIN INFORMACIÓN',
                'facultad' => $data['facultad'] ?? 'SIN INFORMACIÓN',
                'carrera' => $data['carrera'] ?? 'SIN INFORMACIÓN',
                'semestre_actual' => $data['semestreActual'] ?? 'SIN INFORMACIÓN',
                'estado_estudiante' => $data['estadoEstudiante'] ?? 'SIN INFORMACIÓN',
                'estado_civil' => $data['estadoCivil'] ?? 'SIN INFORMACIÓN',
                'email_institucional' => $data['emailInstitucional'] ?? 'SIN INFORMACIÓN',
                'email_personal' => $data['emailPersonal'] ?? 'SIN INFORMACIÓN',
                'telefono' => $data['telefono'] ?? 'SIN INFORMACIÓN',
                'segmentacion_persona' => $segmentacionPersona,
                'periodo' => $data['periodo'] ?? 'SIN INFORMACIÓN',
                'estado_matricula' => $data['estadoMatricula'] ?? 'SIN INFORMACIÓN',
            ]);

            $persona->load(['datosEmpleados', 'datosEstudiantes']);
            return response()->json([$persona]);
        }

        return response()->json(['message' => 'Persona no encontrada'], 404);
    }

    public function updateBienestar(Request $request, $cedula)
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

        return response()->json($persona->load(['datosEmpleados', 'datosMedicos', 'datosEstudiantes']));
    }

    // actualizar datos personales
    public function updateDatosPersonales(Request $request, $cedula)
    {
        // dd($request->all());
        $persona = CpuPersona::where('cedula', $cedula)->first();
        if (!$persona) {
            return response()->json(['message' => 'Persona no encontrada'], 404);
        }

        // Validación de los datos de la persona
        $validatedData = $request->validate([
            'nombres' => 'required|string|max:255',
            'nacionalidad' => 'required|string|max:255',
            'provincia' => 'required|string|max:255',
            'ciudad' => 'required|string|max:255',
            'parroquia' => 'required|string|max:255',
            'direccion' => 'required|string|max:255',
            'sexo' => 'required|string|max:10',
            'fechanaci' => 'required|date',
            'celular' => 'required|string|max:15',
            'tipoetnia' => 'required|string|max:255',
            'discapacidad' => 'required|string|max:3',
            'tipoDiscapacidad' => 'required_if:discapacidad,si|string|max:255',
            'porcentaje' => 'required_if:discapacidad,si|numeric|min:0|max:100',
            // 'imagen' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        Log::info('Datos validados: ', $validatedData);

        // Manejo del archivo de imagen si se proporciona
        if ($request->hasFile('imagen')) {
            $file = $request->file('imagen');
            $filename = $cedula . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('public/images', $filename);
            $validatedData['imagen'] = Storage::url($path);
            Log::info('Imagen subida: ' . $path);
        }

        // Actualización de la persona con los datos validados
        try {
            $persona->update($validatedData);
            Log::info('Datos actualizados para la persona: ' . $cedula);
        } catch (\Exception $e) {
            Log::error('Error al actualizar la persona: ' . $e->getMessage());
            return response()->json(['message' => 'Error al actualizar los datos'], 500);
        }

        // Preparar los datos actualizados para la respuesta
        $updatedData = $persona->only(array_keys($validatedData));

        return response()->json($updatedData);
    }

}