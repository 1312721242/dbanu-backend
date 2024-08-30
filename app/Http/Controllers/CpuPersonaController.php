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
use Illuminate\Support\Facades\Validator;

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
                'id_clasificacion_tipo_usuario'=> 2,
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

   // Aquí para atenciones de bienestar
    public function showBienestar($cedula)
    {
        if (strlen($cedula) < 10) {
            $personas = CpuPersona::where('cedula', 'like', "{$cedula}%")
                ->with(['datosEmpleados', 'datosEstudiantes'])
                ->get();

            foreach ($personas as $persona) {
                $persona->tipoDiscapacidad = $persona->tipo_discapacidad;
                $persona->porcentajeDiscapacidad = $persona->porcentaje_discapacidad;
                $persona->imagen = url('Perfiles/' . $persona->imagen);
            }

            return response()->json($personas);
        }

        $persona = CpuPersona::where('cedula', $cedula)
            ->with(['datosEmpleados', 'datosEstudiantes'])
            ->first();

        if ($persona) {
            // Generar el código de persona
            $codigoPersona = $this->generarCodigoPersona($persona->cedula, $persona->nombres);
            $persona->codigo_persona = $codigoPersona;
            $persona->save();

            $persona->load('datosMedicos'); // Load datosMedicos if available
            $persona->tipoDiscapacidad = $persona->tipo_discapacidad;
            $persona->porcentajeDiscapacidad = $persona->porcentaje_discapacidad;
            $persona->imagen = $persona->imagen;

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
                // Generar el código de persona
                $codigoPersona = $this->generarCodigoPersona($data['cedula'], $data['nombres']);

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
                    'tipo_discapacidad' => $data['tipo_discapacidad'] ?? null,
                    'porcentaje_discapacidad' => $data['porcentaje_discapacidad'] ?? null,
                    'codigo_persona' => $codigoPersona,
                    'imagen' => $data['imagen'] ?? null,
                    'email' => $data['email'] ?? '',
                    'id_clasificacion_tipo_usuario' => 2,
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

            // Generar el código de persona
            $codigoPersona = $this->generarCodigoPersona($cedula, $data['nombres']);

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
                'tipo_discapacidad' => $data['tipo_discapacidad'] ?? null,
                'porcentaje_discapacidad' => $data['porcentaje_discapacidad'] ?? null,
                'codigo_persona' => $codigoPersona,
                'imagen' => $data['imagen'] ?? null,
                'id_clasificacion_tipo_usuario' => 1,
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

    private function generarCodigoPersona($cedula, $nombres)
    {
        $cedulaParte = substr($cedula, 0, 4); // Tomar los primeros 4 dígitos de la cédula
        $nombrePartes = explode(' ', $nombres);
        $primerasIniciales = '';
        $ultimasIniciales = '';

        // Tomar las primeras dos iniciales
        if (count($nombrePartes) > 0) {
            $primerasIniciales .= strtoupper($nombrePartes[0][0]);
            if (isset($nombrePartes[1])) {
                $primerasIniciales .= strtoupper($nombrePartes[1][0]);
            }
        }

        // Tomar las últimas iniciales
        if (count($nombrePartes) > 2) {
            for ($i = 2; $i < count($nombrePartes); $i++) {
                $ultimasIniciales .= strtoupper($nombrePartes[$i][0]);
            }
        }

        // Concatenar las primeras iniciales, la parte de la cédula y las últimas iniciales
        $codigoPersona = $primerasIniciales . $cedulaParte . $ultimasIniciales;

        return $codigoPersona;
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
    Log::info('Entrando al método updateDatosPersonales');

    $persona = CpuPersona::where('cedula', $cedula)->first();
    if (!$persona) {
        Log::warning('Persona no encontrada con cedula: ' . $cedula);
        return response()->json(['message' => 'Persona no encontrada'], 404);
    }

    // Validación de los datos
    $validator = Validator::make($request->all(), [
        'nombres' => 'required|string',
        'nacionalidad' => 'required|string',
        'provincia' => 'required|string',
        'ciudad' => 'required|string',
        'parroquia' => 'required|string',
        'direccion' => 'required|string',
        'sexo' => 'required|string',
        'fechanaci' => 'required|date',
        'celular' => 'required|string',
        'tipoetnia' => 'required|string',
        'discapacidad' => 'nullable|string',
        'imagen' => 'nullable|image|max:2048', // Validación para la imagen
        'tipoDiscapacidad' => 'nullable|string', // Validación para tipoDiscapacidad
        'porcentaje' => 'nullable|numeric', // Validación para porcentaje
    ]);

    if ($validator->fails()) {
        Log::info('Errores de validación:', $validator->errors()->all());
        return response()->json(['errors' => $validator->errors()], 422);
    }

    $validatedData = $validator->validated();
    Log::info('Datos validados:', $validatedData);

    // Iniciar transacción
    DB::beginTransaction();
    try {
        // Actualización de los datos
        $persona->update($validatedData);

        // Manejo de archivo de imagen
        if ($request->hasFile('imagen')) {
            $file = $request->file('imagen');

            // Verificar si el archivo es válido
            if ($file->isValid()) {
                // Eliminar imagen anterior si existe
                if ($persona->imagen) {
                    Storage::delete('Perfiles/' . $persona->imagen);
                }

                // Construir el nuevo nombre de archivo
                $extension = $file->getClientOriginalExtension();
                $newFilename = "{$cedula}.{$extension}";

                // Almacenar el archivo en la misma ruta que los demás archivos
                $filePath = $file->move(public_path("Perfiles/"), $newFilename);
                $persona->imagen = basename($filePath); // Guardar la ruta relativa en la base de datos
                Log::info('Imagen subida con éxito: ' . $persona->imagen);
            } else {
                Log::info('Archivo de imagen no válido.');
                DB::rollBack();
                return response()->json(['error' => 'Invalid image file.'], 422);
            }
        } else {
            Log::info('No se ha subido ninguna imagen.');
        }

        // Actualizar tipoDiscapacidad y porcentaje si están presentes en la solicitud
        if ($request->has('tipoDiscapacidad')) {
            $persona->tipo_discapacidad = $request->input('tipoDiscapacidad');
        }
        if ($request->has('porcentaje')) {
            $persona->porcentaje_discapacidad = $request->input('porcentaje');
        }

        $persona->save();

        // Confirmar transacción
        DB::commit();
        Log::info('Datos actualizados con éxito para la persona con cedula: ' . $cedula);

        return response()->json($persona);
    } catch (\Exception $e) {
        // Revertir transacción
        DB::rollBack();
        Log::error('Error al actualizar los datos: ' . $e->getMessage());
        return response()->json(['error' => 'Error al actualizar los datos'], 500);
    }
}
}
