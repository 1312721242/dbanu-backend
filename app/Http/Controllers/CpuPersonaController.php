<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CpuPersona;
use App\Models\CpuDatosEmpleado;
use App\Models\CpuDatosMedicos;
use App\Models\CpuDatosEstudiantes;
use App\Models\CpuDatosUsuarioExterno;
use App\Models\CpuTipoDiscapacidad;
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
            ->with(['datosEmpleados', 'tipoDiscapacidad']) // Se añade la relación
            ->get();
        return response()->json($personas);
    }

    $persona = CpuPersona::where('cedula', $cedula)
        ->with(['datosEmpleados', 'tipoDiscapacidad']) // Se añade la relación
        ->first();

    if ($persona) {
        return response()->json($persona);
    }

    // Llamada externa en caso de no encontrar la persona
    try {
        $response = Http::asForm()->post('https://login.microsoftonline.com/31a17900-7589-4cfc-b11a-f4e83c27b8ed/oauth2/v2.0/token', [
            'grant_type' => 'client_credentials',
            'client_id' => '1111b1c0-8b4f-4f50-96ea-ea4cc2df1c6d',
            'client_secret' => 'iZH8Q~TRpKFW5PCG4OlBw-R1SDDnpT-611myKasT',
            'scope' => 'https://service.flow.microsoft.com//.default'
        ]);

        if ($response->failed()) {
            Log::error('Error al obtener el token de acceso: ' . $response->status() . ' ' . $response->body());
            return response()->json(['error' => 'Error al obtener el token de acceso'], 500);
        }

        $access_token = $response->json()['access_token'];
        $identificacion = $cedula;

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $access_token,
            'Content-Type' => 'application/json'
        ])->post('https://prod-160.westus.logic.azure.com/workflows/79256a92249b4f85bc6c0737d8d17d10/triggers/manual/paths/invoke/cedula/{identificacion}?api-version=2016-06-01', [
            'identificacion' => $identificacion
        ]);

        if ($response->failed()) {
            Log::error('Error al enviar la solicitud a Azure Logic Apps: ' . $response->status() . ' ' . $response->body());
            return response()->json(['error' => 'Error al enviar la solicitud a Azure Logic Apps'], 500);
        }

    } catch (\Exception $e) {
        Log::error('Error al obtener el token de acceso: ' . $e->getMessage());
        return response()->json(['error' => 'Error al obtener el token de acceso'], 500);
    }

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
            'tipo_discapacidad' => $data['tipo_discapacidad'], // Se guarda el ID de discapacidad
            'id_clasificacion_tipo_usuario' => 2,
            'ocupacion' => $data['ocupacion'],
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

        // Cargar la relación con el tipo de discapacidad
        $persona->load(['datosEmpleados', 'tipoDiscapacidad']);

        return response()->json([$persona]);
    }

    return response()->json(['message' => 'Persona no encontrada'], 404);
}


    // Aquí para atenciones de bienestar
    public function showBienestar($cedula)
    {
        Log::info("CEDULA RECIBIDA:  '$cedula'");
        if (strlen($cedula) <= 9) {
            $personas = CpuPersona::where('cedula', 'like', "{$cedula}%")
                ->with(['datosEmpleados', 'datosEstudiantes', 'datosExternos', 'datosMedicos'])
                ->get();

            foreach ($personas as $persona) {
                $persona->tipoDiscapacidad = $persona->tipo_discapacidad;
                $persona->porcentajeDiscapacidad = $persona->porcentaje_discapacidad;
                $persona->imagen = url('Perfiles/' . $persona->imagen);
            }

            return response()->json($personas);
        }

        $persona = CpuPersona::where('cedula', $cedula)
            ->with(['datosEmpleados', 'datosEstudiantes', 'datosExternos', 'datosMedicos'])
            ->first();

        if ($persona) {
            // Generar el código de persona
            $codigoPersona = $this->generarCodigoPersona($persona->cedula, $persona->nombres);
            $persona->codigo_persona = $codigoPersona;
            $persona->save();

            $persona->load('datosMedicos'); // Load datosMedicos if available
            $persona->tipoDiscapacidad = $persona->tipo_discapacidad;
            $persona->porcentajeDiscapacidad = $persona->porcentaje_discapacidad;
            $persona->embarazada = $persona->embarazada;
            $persona->ultima_fecha_mestruacion = $persona->ultima_fecha_mestruacion;
            $persona->imagen = $persona->imagen;

            return response()->json($persona);
        }

        // First API call
        // $response = Http::get("https://apps2.uleam.edu.ec/DATHApi/api/personal/{$cedula}/bienestar");
        try {
            $response = Http::withOptions(['verify' => false])->asForm()->post('https://login.microsoftonline.com/31a17900-7589-4cfc-b11a-f4e83c27b8ed/oauth2/v2.0/token', [
                'grant_type' => 'client_credentials',
                'client_id' => '1111b1c0-8b4f-4f50-96ea-ea4cc2df1c6d',
                'client_secret' => 'iZH8Q~TRpKFW5PCG4OlBw-R1SDDnpT-611myKasT',
                'scope' => 'https://service.flow.microsoft.com//.default'
            ]);

            if ($response->failed()) {
                Log::error('Error al obtener el token de acceso: ' . $response->status() . ' ' . $response->body());
                return response()->json(['error' => 'Error al obtener el token de acceso'], 500);
            }

            $access_token = $response->json()['access_token'];

            Log::info("TOKEN OBTENIDO: " . $access_token);
            $identificacion = $cedula;

            // Construcción de la URL con el identificador
            $url = "https://prod-160.westus.logic.azure.com/workflows/79256a92249b4f85bc6c0737d8d17d10/triggers/manual/paths/invoke/cedula/{$identificacion}?api-version=2016-06-01";

            // Llamar a Azure Logic Apps con el token correcto y el identificador
            $response = Http::withOptions(['verify' => false])->withHeaders([
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type' => 'application/json'
            ])->get($url);

            if ($response->failed()) {
                Log::error('Error al enviar la solicitud a Azure Logic Apps: ' . $response->status() . ' ' . $response->body());
                return response()->json(['error' => 'Error al enviar la solicitud a Azure Logic Apps'], 500);
            }

            $data = $response->json();
            Log::info('RESPUESTA API (empleados): ' . json_encode($data));

            // return response()->json($response->json());
        } catch (\Exception $e) {
            Log::error('Error al obtener el token de acceso: ' . $e->getMessage());
            return response()->json(['error' => 'Error al obtener el token de acceso'], 500);
        }
        if (isset($response) && $response->successful()) {
            $data = $response->json();

            // Verificar si está vacío o no
            $isEmptyData = empty($data['Cedula'])
                && empty($data['Nombres'])
                && empty($data['PaisNacimiento'])
                && empty($data['ProvinciaDomicilio'])
                && empty($data['CantonDomicilio'])
                && empty($data['ParroquiaDomicilio'])
                && empty($data['Direccion'])
                && empty($data['Sexo'])
                && empty($data['FechaNacimiento'])
                && empty($data['TelefonoMovil'])
                && empty($data['TipoEtnia']);

            // Si NO está vacío, creamos persona y retornamos

            // // Check if the data returned is essentially empty
            // $isEmptyData = empty($data['cedula']) && empty($data['nombres']) && empty($data['nacionalidad']) &&
            //     empty($data['provincia']) && empty($data['ciudad']) && empty($data['parroquia']) &&
            //     empty($data['direccion']) && empty($data['sexo']) && empty($data['fechanaci']) &&
            //     empty($data['celular']) && empty($data['tipoetnia']);

            if (!$isEmptyData) {
                // Generar el código de persona
                $codigoPersona = $this->generarCodigoPersona($data['Cedula'], $data['Nombres']);

                $personaData = [
                    'cedula' => $data['Cedula'] ?? '',
                    'nombres' => $data['Nombres'] ?? 'SIN INFORMACIÓN',
                    'nacionalidad' => $data['PaisNacimiento'] ?? 'SIN INFORMACIÓN',
                    'provincia' => $data['ProvinciaDomicilio'] ?? 'SIN INFORMACIÓN',
                    'ciudad' => $data['CantonDomicilio'] ?? 'SIN INFORMACIÓN',
                    'parroquia' => $data['ParroquiaDomicilio'] ?? 'SIN INFORMACIÓN',
                    'direccion' => $data['Direccion'] ?? 'SIN INFORMACIÓN',
                    'sexo' => $data['Sexo'] ?? 'SIN INFORMACIÓN',
                    'fechanaci' => $data['FechaNacimiento'] ?? '1900-01-01',
                    'celular' => $data['TelefonoMovil'] ?? 'SIN INFORMACIÓN',
                    'tipoetnia' => $data['TipoEtnia'] ?? 'SIN INFORMACIÓN',
                    'discapacidad' => $data['TieneDiscapacidad'] ?? 'SIN INFORMACIÓN',
                    'porcentaje_discapacidad' => (
                        !empty($data['PorcentajeDiscapacidad'])
                        && is_numeric($data['PorcentajeDiscapacidad'])
                    ) ? (float)$data['PorcentajeDiscapacidad'] : 0,
                    'codigo_persona' => $codigoPersona,
                    'imagen' => $data['imagen'] ?? null,
                    'email' => $data['CorreoInstitucional'] ?? '',
                    'id_clasificacion_tipo_usuario' => 2,
                    'ocupacion' => $data['ocupacion'] ?? null,
                    'estado_civil' => $data['EstadCcivil'] ?? 'SIN INFORMACIÓN',
                    'bono_desarrollo' => $data['bono_desarrollo'] ?? 'SIN INFORMACIÓN',
                ];

                // 2. Busca, si existe, la descripción en la tabla `cpu_tipos_discapacidad`
                if (!empty($data['TipoDiscapacidad'])) {
                    $tipoDiscapacidad = DB::table('cpu_tipos_discapacidad')
                        ->where('descripcion', $data['TipoDiscapacidad'])
                        ->value('id'); // Devuelve el id, o null si no lo encuentra

                    // 3. Si la descripción existe, asigna el ID
                    if ($tipoDiscapacidad) {
                        $personaData['tipo_discapacidad'] = $tipoDiscapacidad;
                    }
                    // De lo contrario, no se asigna nada, evitando la violación de FK
                }

                // 4. Finalmente, creas la persona
                $persona = CpuPersona::create($personaData);

                CpuDatosEmpleado::create([
                    'id_persona' => $persona->id,
                    'emailinstitucional' => $data['CorreoInstitucional'] ?? 'SIN INFORMACIÓN',
                    'puesto' => $data['Cargo'] ?? 'SIN INFORMACIÓN',
                    'regimen1' => $data['Regimen'] ?? 'SIN INFORMACIÓN',
                    'correopersonal' => $data['CorreoPersonal'] ?? 'SIN INFORMACIÓN',
                    'unidad' => $data['NombreSubProceso'] ?? 'SIN INFORMACIÓN',
                    'carrera' => $data['NombreSeccion'] ?? 'SIN INFORMACIÓN',
                    'nombreproceso' => $data['NombreProceso'] ?? null,
                    'sector' => $data['Sector'] ?? 'SIN INFORMACIÓN',
                    'referencia' => $data['Referencia'] ?? 'SIN INFORMACIÓN',
                    'fechaingre' => $data['FechaIngreso'] ?? '1900-01-01',
                ]);

                Log::info('Datos del empleado creado: ' . json_encode($persona->datosEmpleados));

                CpuDatosMedicos::create([
                    'id_persona' => $persona->id,
                    'tipo_sangre' => [
                        "A+" => 1,
                        "A-" => 2,
                        "B+" => 3,
                        "B-" => 4,
                        "AB+" => 5,
                        "AB-" => 6,
                        "O+" => 7,
                        "O-" => 8,
                    ][$data['TipoSangre']] ?? null,

                ]);

                Log::info('Datos del médico creado: ' . json_encode($persona->datosMedicos));

                $persona->load(['datosEmpleados', 'datosEstudiantes']); // Load datosEstudiantes if available
                return response()->json([$persona]);
            }
        }

        //Second API call if first API doesn't provide any data
        Log::info('ENTRADO API ESTUDIANTES');
        try {
            Log::info('DENTRO API ESTUDIANTES');
            $response = Http::asForm()->post('https://login.microsoftonline.com/31a17900-7589-4cfc-b11a-f4e83c27b8ed/oauth2/v2.0/token', [
                'grant_type' => 'client_credentials',
                'client_id' => '13e24fa4-9c64-4653-a96c-20964510b52a',
                'client_secret' => 'ywq8Q~1mk.SSMpJV1KjeUZPZfY~~1diPvVCT0c.b',
                'scope' => 'https://service.flow.microsoft.com//.default'
            ]);

            if ($response->failed()) {
                Log::error('Error al obtener el token de acceso: ' . $response->status() . ' ' . $response->body());
                return response()->json(['error' => 'Error al obtener el token de acceso'], 500);
            }

            $access_token = $response->json()['access_token'];
            $identificacion = $cedula;


            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type' => 'application/json'
            ])->post('https://prod-146.westus.logic.azure.com:443/workflows/033f8b54b4cc42f4ac0fdea481c0c27c/triggers/manual/paths/invoke?api-version=2016-06-01', [
                'identificacion' => $identificacion
            ]);

            if ($response->failed()) {
                Log::error('Error al enviar la solicitud a Azure Logic Apps: ' . $response->status() . ' ' . $response->body());
                return response()->json(['error' => 'Error al enviar la solicitud a Azure Logic Apps'], 500);
            }

            // return response()->json($response->json());
        } catch (\Exception $e) {
            Log::error('Error al obtener el token de acceso: ' . $e->getMessage());
            return response()->json(['error' => 'Error al obtener el token de acceso'], 500);
        }
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
                    ->select('segmento_persona AS segmento') // <--- ALIAS
                    ->first();
                if (!$segmentacionData) {
                    $segmentacionData = DB::table('public.cpu_mtn_2018_2022')
                        ->select('segmento') // <-- ya trae 'segmento'
                        ->first();
                }
                // Ahora las dos tablas devuelven la propiedad "segmento"
                $segmentacionPersona = $segmentacionData
                    ? $segmentacionData->segmento
                    : $segmentacionPersona;
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
                // 'ocupacion' => $data['ocupacion'],
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
            'nombres',
            'nacionalidad',
            'provincia',
            'ciudad',
            'parroquia',
            'direccion',
            'sexo',
            'fechanaci',
            'celular',
            'tipoetnia',
            'discapacidad',
            'tipo_discapacidad',
            'ocupacion'
        ]));

        $persona->datosEmpleados()->update($request->only([
            'emailinstitucional',
            'puesto',
            'regimen1',
            'modalidad',
            'unidad',
            'carrera',
            'idsubproceso',
            'escala1',
            'estado',
            'fechaingre'
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
            'nombres' => 'nullable|string',
            'nacionalidad' => 'nullable|string',
            'provincia' => 'nullable|string',
            'ciudad' => 'nullable|string',
            'parroquia' => 'nullable|string',
            'direccion' => 'nullable|string',
            'sexo' => 'nullable|string',
            'fechanaci' => 'nullable|date',
            'celular' => 'nullable|string',
            'tipoetnia' => 'nullable|string',
            'discapacidad' => 'nullable|string',
            'imagen' => 'nullable|image|max:2048', // Validación para la imagen
            'tipoDiscapacidad' => 'nullable|string', // Validación para tipoDiscapacidad
            'porcentaje' => 'nullable|numeric', // Validación para porcentaje
            'ocupacion' => 'nullable|string', // Validación para ocupacion
            'bonoDesarrollo' => 'nullable|string', // Validación para bonoDesarrollo
            'estadoCivil' => 'nullable|string', // Validación para estadoCivil
            'email' => 'nullable|email', // Validación para email
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

            // Actualizar tipoDiscapacidad, porcentaje, bonoDesarrollo y estadoCivil si están presentes en la solicitud
            if ($request->has('tipoDiscapacidad')) {
                $persona->tipo_discapacidad = $request->input('tipoDiscapacidad');
            }
            if ($request->has('porcentaje')) {
                $persona->porcentaje_discapacidad = $request->input('porcentaje');
            }
            if ($request->has('bonoDesarrollo')) {
                $persona->bono_desarrollo = $request->input('bonoDesarrollo');
            }
            if ($request->has('estadoCivil')) {
                $persona->estado_civil = $request->input('estadoCivil');
            }

            // Actualizar el email en la tabla correspondiente
            if ($request->has('email')) {
                $email = $request->input('email');
                $idPersona = $persona->id;

                // Verificar en qué tabla se encuentra el id_persona y actualizar el email
                if (CpuDatosUsuarioExterno::where('id_persona', $idPersona)->exists()) {
                    CpuDatosUsuarioExterno::where('id_persona', $idPersona)->update(['email' => $email]);
                } elseif (CpuDatosEmpleado::where('id_persona', $idPersona)->exists()) {
                    CpuDatosEmpleado::where('id_persona', $idPersona)->update(['emailinstitucional' => $email]);
                } elseif (CpuDatosEstudiantes::where('id_persona', $idPersona)->exists()) {
                    CpuDatosEstudiantes::where('id_persona', $idPersona)->update(['email_personal' => $email]);
                }
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

    //FUNCION PARA AGREGAR USUARIOS EXTERNOS
    public function store(Request $request)
    {
        // Validar los datos enviados desde el formulario
        $validator = Validator::make($request->all(), [
            'identificacion' => 'required|string|min:10',
            'nombres' => 'required|string',
            'nacionalidad' => 'required|string',
            'provincia' => 'required|string',
            'ciudad' => 'required|string',
            'parroquia' => 'required|string',
            'direccion' => 'required|string',
            'sexo' => 'required|string',
            'fechanaci' => 'required|date',
            'celular' => 'required|string|max:10',
            'tipoetnia' => 'required|string',
            'discapacidad' => 'nullable|string',
            'imagen' => 'nullable|image|max:2048',
            'tipoDiscapacidad' => 'nullable|string',
            'porcentajeDiscapacidad' => 'nullable|numeric',
            'id_clasificacion_tipo_usuario' => 'required|integer',
            'ocupacion' => 'nullable|string',
            'bonoDesarrollo' => 'nullable|string',
            'estadoCivil' => 'nullable|string',
            'id_tipo_usuario' => 'required|integer',
            // datos de la secretaria
            'email' => 'required|string',
            'referencia' => 'nullable|string',
            'numeroMatricula' => 'nullable|string',
            'tipoBeca' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Obtener los datos validados
        $validatedData = $validator->validated();

        // Generar el código de persona a partir de la identificación y nombres
        $codigoPersona = $this->generarCodigoPersona($validatedData['identificacion'], $validatedData['nombres']);

        // Comenzar una transacción
        DB::beginTransaction();

        try {
            // Crear la nueva persona, asignando 'tipo_discapacidad' y 'porcentaje_discapacidad' solo si existen
            $personaData = [
                'cedula' => $validatedData['identificacion'],
                'nombres' => $validatedData['nombres'],
                'nacionalidad' => $validatedData['nacionalidad'],
                'provincia' => $validatedData['provincia'],
                'ciudad' => $validatedData['ciudad'],
                'parroquia' => $validatedData['parroquia'],
                'direccion' => $validatedData['direccion'],
                'sexo' => $validatedData['sexo'],
                'fechanaci' => $validatedData['fechanaci'],
                'celular' => $validatedData['celular'],
                'tipoetnia' => $validatedData['tipoetnia'],
                'discapacidad' => $validatedData['discapacidad'],
                'codigo_persona' => $codigoPersona,
                'id_clasificacion_tipo_usuario' => $validatedData['id_clasificacion_tipo_usuario'],
                'tipo_discapacidad' => $validatedData['tipoDiscapacidad'],
                'porcentaje_discapacidad' => $validatedData['porcentajeDiscapacidad'],
                'ocupacion' => $validatedData['ocupacion'],
                'bono_desarrollo' => $validatedData['bonoDesarrollo'],
                'estado_civil' => $validatedData['estadoCivil'],
                'id_tipo_usuario' => $validatedData['id_tipo_usuario'],
            ];

            // Solo agregar los campos si están presentes
            // if (!empty($validatedData['tipo_discapacidad'])) {
            //     $personaData['tipo_discapacidad'] = $validatedData['tipo_discapacidad'];
            // }

            // if (!empty($validatedData['porcentaje_discapacidad'])) {
            //     $personaData['porcentaje_discapacidad'] = $validatedData['porcentaje_discapacidad'];
            // }

            // Crear la nueva persona
            $persona = CpuPersona::create($personaData);

            // Manejar la imagen de perfil, si existe
            if ($request->hasFile('imagen')) {
                $file = $request->file('imagen');
                if ($file->isValid()) {
                    $extension = $file->getClientOriginalExtension();
                    $newFilename = "{$persona->cedula}.{$extension}";
                    $filePath = $file->move(public_path("Perfiles/"), $newFilename);
                    $persona->imagen = basename($filePath);
                }
            }

            $persona->save();

            // Guardar los datos de usuario externo
            $usuarioExternoData = [
                'id_persona' => $persona->id,
                'email' => $validatedData['email'],
                'referencia' => $validatedData['referencia'],
                'numero_matricula' => $validatedData['numeroMatricula'],
                'tipo_beca' => $validatedData['tipoBeca'],
            ];

            CpuDatosUsuarioExterno::create($usuarioExternoData);


            // Confirmar la transacción
            DB::commit();

            return response()->json($persona, 201);
        } catch (\Exception $e) {
            // Revertir la transacción en caso de error
            DB::rollBack();
            return response()->json([
                'error' => 'Error al guardar los datos',
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    //FUNCION PARA AGREGAR USUARIOS EXTERNOS
    public function storeSecretaria(Request $request)
    {
        // Validar los datos enviados desde el formulario
        $validator = Validator::make($request->all(), [
            'identificacion' => 'required|string|min:10',
            'nombres' => 'required|string',
            'email' => 'required|string',
            'celular' => 'required|string|max:10',
            'nacionalidad' => 'nullable|string',
            'provincia' => 'nullable|string',
            'ciudad' => 'nullable|string',
            'parroquia' => 'nullable|string',
            'direccion' => 'nullable|string',
            'sexo' => 'nullable|string',
            'fechanaci' => 'nullable|date',
            'tipoetnia' => 'nullable|string',
            'discapacidad' => 'nullable|string',
            'imagen' => 'nullable|image|max:2048',
            'tipoDiscapacidad' => 'nullable|string',
            'porcentajeDiscapacidad' => 'nullable|numeric',
            'id_clasificacion_tipo_usuario' => 'nullable|integer',
            'ocupacion' => 'nullable|string',
            'bonoDesarrollo' => 'nullable|string',
            'estadoCivil' => 'nullable|string',
            'id_tipo_usuario' => 'nullable|integer',
            // datos de la secretaria
            'email' => 'required|string',
            'referencia' => 'nullable|string',
            'numeroMatricula' => 'nullable|string',
            'tipoBeca' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Obtener los datos validados
        $validatedData = $validator->validated();

        // Generar el código de persona a partir de la identificación y nombres
        $codigoPersona = $this->generarCodigoPersona($validatedData['identificacion'], $validatedData['nombres']);

        // Comenzar una transacción
        DB::beginTransaction();

        try {
            // Crear la nueva persona, asignando 'tipo_discapacidad' y 'porcentaje_discapacidad' solo si existen
            $personaData = [
                'cedula' => $validatedData['identificacion'],
                'nombres' => $validatedData['nombres'],
                'nacionalidad' => $validatedData['nacionalidad'],
                'provincia' => $validatedData['provincia'],
                'ciudad' => $validatedData['ciudad'],
                'parroquia' => $validatedData['parroquia'],
                'direccion' => $validatedData['direccion'],
                'sexo' => $validatedData['sexo'],
                'fechanaci' => $validatedData['fechanaci'],
                'celular' => $validatedData['celular'],
                'tipoetnia' => $validatedData['tipoetnia'],
                'discapacidad' => $validatedData['discapacidad'],
                'codigo_persona' => $codigoPersona,
                'id_clasificacion_tipo_usuario' => $validatedData['id_clasificacion_tipo_usuario'],
                'tipo_discapacidad' => $validatedData['tipoDiscapacidad'],
                'porcentaje_discapacidad' => $validatedData['porcentajeDiscapacidad'],
                'ocupacion' => $validatedData['ocupacion'],
                'bono_desarrollo' => $validatedData['bonoDesarrollo'],
                'estado_civil' => $validatedData['estadoCivil'],
                'id_tipo_usuario' => $validatedData['id_tipo_usuario'],
            ];

            // Crear la nueva persona
            $persona = CpuPersona::create($personaData);

            // Manejar la imagen de perfil, si existe
            if ($request->hasFile('imagen')) {
                $file = $request->file('imagen');
                if ($file->isValid()) {
                    $extension = $file->getClientOriginalExtension();
                    $newFilename = "{$persona->cedula}.{$extension}";
                    $filePath = $file->move(public_path("Perfiles/"), $newFilename);
                    $persona->imagen = basename($filePath);
                }
            }

            $persona->save();

            // Guardar los datos de usuario externo
            $usuarioExternoData = [
                'id_persona' => $persona->id,
                'email' => $validatedData['email'],
                'referencia' => $validatedData['referencia'],
                'numero_matricula' => $validatedData['numeroMatricula'],
                'tipo_beca' => $validatedData['tipoBeca'],
            ];

            CpuDatosUsuarioExterno::create($usuarioExternoData);

            // Confirmar la transacción
            DB::commit();

            return response()->json($persona, 201);
        } catch (\Exception $e) {
            // Revertir la transacción en caso de error
            DB::rollBack();
            return response()->json([
                'error' => 'Error al guardar los datos',
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }
}
