<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\CpuLegalizacionMatricula;
use Illuminate\Support\Facades\DB;

class CpuLegalizacionMatriculaController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    //funcion para subir los archivos de la persona
    public function uploadPdf(Request $request)
    {
        // Verificar si se han subido los archivos
        if (!$request->hasFile('cedula') || !$request->hasFile('titulo') || !$request->hasFile('cupo')) {
            return response()->json(['error' => 'Not all files uploaded.'], 422);
        }

        $uploadedFiles = [];

        // Recorrer y procesar cada archivo
        foreach (['cedula', 'titulo', 'cupo'] as $paramName) {
            $file = $request->file($paramName);

            // Verificar si el archivo es válido
            if ($file->isValid()) {
                $filename = $file->getClientOriginalName();

                $user = auth()->guard('sanctum')->user();

                if (!$user) {
                    return response()->json(['error' => 'User not authenticated.'], 401);
                }

                // Construir el nuevo nombre de archivo
                $newFilename = "{$paramName}_{$user->id_periodo}_{$user->cedula}.pdf";

                // Validar el tipo y tamaño del archivo
                $validator = Validator::make([$paramName => $file], [
                    $paramName => 'required|mimes:pdf|max:8192', // Max 8MB
                ]);

                if ($validator->fails()) {
                    return response()->json(['error' => $validator->messages()], 422);
                }

                // Procesar el archivo
                $file->move(public_path("Files/"), $newFilename);
                $uploadedFiles[$paramName] = $newFilename;
            } else {
                return response()->json(['error' => 'Invalid file.'], 422);
            }
        }

        // Actualizar la tabla cpu_legalizacion_matricula con las rutas de los archivos
        $userId = $user->id;
        $legalizacionMatricula = CpuLegalizacionMatricula::where('id', $userId)->first();
        if (!$legalizacionMatricula) {
            $legalizacionMatricula = new CpuLegalizacionMatricula();
            $legalizacionMatricula->id_usuario = $userId;
        }

        $legalizacionMatricula->copia_identificacion = $uploadedFiles['cedula'];
        $legalizacionMatricula->estado_identificacion = 12;
        $legalizacionMatricula->copia_titulo_acta_grado = $uploadedFiles['titulo'];
        $legalizacionMatricula->estado_titulo = 12;
        $legalizacionMatricula->copia_aceptacion_cupo = $uploadedFiles['cupo'];
        $legalizacionMatricula->estado_cupo = 12;
        $legalizacionMatricula->save();

        return response()->json(["mensaje" => "Archivos subidos correctamente", "files" => $uploadedFiles]);
    }

    //funcion para tomar los datos de la persona

    public function getPersonData(Request $request)
    {
        $user = auth()->guard('sanctum')->user();

        if (!$user) {
            return response()->json(['error' => 'User not authenticated.'], 401);
        }

        $personData = [
            'id' => $user->id,
            'id_periodo' => $user->id_periodo,
            'email' => $user->email,
            'cedula' => $user->cedula,
            'apellidos' => $user->apellidos,
            'nombres' => $user->nombres,
            'copia_identificacion' => $user->copia_identificacion,
            'estado_identificacion' => $user->estado_identificacion,
            'copia_titulo_acta_grado' => $user->copia_titulo_acta_grado,
            'estado_titulo' => $user->estado_titulo,
            'copia_aceptacion_cupo' => $user->copia_aceptacion_cupo,
            'estado_cupo' => $user->estado_cupo,
        ];

        return response()->json($personData);
    }


}
