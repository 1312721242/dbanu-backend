<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\CpuLegalizacionMatricula;
use Illuminate\Support\Facades\DB;
use App\Models\CpuMatriculaConfiguracion;
use App\Models\CpuCasosMatricula;
use App\Models\CpuSecretariaMatricula;



class CpuLegalizacionMatriculaController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

      public function uploadPdf(Request $request)
    {
        // Obtener la configuración de matrícula activa
        $matriculaConfiguracion = CpuMatriculaConfiguracion::where('id_estado', 8)->first();

        if (!$matriculaConfiguracion) {
            return response()->json(['error' => 'No hay un periodo de matrícula activo.'], 401);
        }

        // Verificar si la fecha actual está dentro del rango de fechas de matrícula
        $currentDate = now();
        if ($currentDate < $matriculaConfiguracion->fecha_inicio_matricula_ordinaria ||
            $currentDate > $matriculaConfiguracion->fecha_fin_matricula_extraordinaria) {
            return response()->json(['error' => 'La subida de archivos no está permitida fuera del periodo de matrícula.'], 401);
        }

        $uploadedFiles = [];

        // Recorrer y procesar cada archivo
        foreach (['cedula', 'titulo', 'cupo'] as $paramName) {
            if (!$request->hasFile($paramName)) {
                continue; // Saltar si el archivo no se ha subido
            }

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
                    $paramName => 'required|mimes:pdf|max:5120', // Max 5MB
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

        if (isset($uploadedFiles['cedula'])) {
            $legalizacionMatricula->copia_identificacion = $uploadedFiles['cedula'];
            $legalizacionMatricula->estado_identificacion = 12;
        }

        if (isset($uploadedFiles['titulo'])) {
            $legalizacionMatricula->copia_titulo_acta_grado = $uploadedFiles['titulo'];
            $legalizacionMatricula->estado_titulo = 12;
        }

        if (isset($uploadedFiles['cupo'])) {
            $legalizacionMatricula->copia_aceptacion_cupo = $uploadedFiles['cupo'];
            $legalizacionMatricula->estado_cupo = 12;
        }

        $legalizacionMatricula->save();

        // Verificar si ya existe un caso para este id_legalizacion_matricula en el periodo actual
        $casoExistente = CpuCasosMatricula::where('id_legalizacion_matricula', $legalizacionMatricula->id)
            ->whereHas('legalizacionMatricula', function ($query) use ($matriculaConfiguracion) {
                $query->where('id_periodo', $matriculaConfiguracion->id_periodo);
            })
            ->first();

        if ($casoExistente) {
            // Actualizar el caso existente
            $casoExistente->id_estado = 13;
            $casoExistente->save();
        } else {
            // Crear un nuevo caso de matrícula
            $casoMatricula = new CpuCasosMatricula();
            $casoMatricula->id_legalizacion_matricula = $legalizacionMatricula->id;
            $casoMatricula->id_estado = 13; // Estado inicial del caso

            // Asignar el caso a la secretaría con menos casos pendientes en la misma sede
            $idSede = $legalizacionMatricula->id_sede;
            $secretaria = CpuSecretariaMatricula::where('id_sede', $idSede)
                ->where('habilitada', true)
                ->orderBy('casos_pendientes', 'asc')
                ->first();

            if ($secretaria) {
                $secretaria->casos_pendientes++;
                $secretaria->save();
                $casoMatricula->id_secretaria = $secretaria->id;
            }

            $casoMatricula->save();
        }

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
            'copia_identificacion' => url('Files/' .$user->copia_identificacion),
            'estado_identificacion' => $user->estado_identificacion,
            'copia_titulo_acta_grado' => url('Files/' .  $user->copia_titulo_acta_grado),
            'estado_titulo' => $user->estado_titulo,
            'copia_aceptacion_cupo' => url('Files/' .  $user->copia_aceptacion_cupo),
            'estado_cupo' => $user->estado_cupo,
        ];

        return response()->json($personData);
    }


}
