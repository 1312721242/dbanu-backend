<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\CpuLegalizacionMatricula;
use Illuminate\Support\Facades\DB;
// use Maatwebsite\Excel\Concerns\Exportable;
// use Maatwebsite\Excel\Concerns\FromArray;
// use Maatwebsite\Excel\Concerns\WithHeadings;
// use Maatwebsite\Excel\Concerns\ToModel;
// use Maatwebsite\Excel\Concerns\WithStartRow;


class CpuLegalizacionMatriculaController extends Controller
{
    // public function __construct()
    // {
    //     $this->middleware('auth:api');
    // } 

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
        $legalizacionMatricula->copia_titulo_acta_grado = $uploadedFiles['titulo'];
        $legalizacionMatricula->copia_aceptacion_cupo = $uploadedFiles['cupo'];
        $legalizacionMatricula->save();

        return response()->json(["mensaje" => "Archivos subidos correctamente", "files" => $uploadedFiles]);
    }

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
        'copia_titulo_acta_grado' => $user->copia_titulo_acta_grado,
        'copia_aceptacion_cupo' => $user->copia_aceptacion_cupo,
    ];

    return response()->json($personData);
}

// //generar plantilla de archivo
// public function exportTemplate()
// {
//     $headers = [
//         'id_periodo (integer)',
//         'id_registro_nacional (text)',
//         'id_postulacion (integer)',
//         'ciudad_campus (text)',
//         'id_sede (integer)',
//         'id_facultad (integer)',
//         'id_carrera (integer)',
//         'email (text)',
//         'cedula (text)',
//         'apellidos (text)',
//         'nombres (text)',
//         'genero (text)',
//         'etnia (text)',
//         'discapacidad (text)',
//         'segmento_persona (text)',
//         'nota_postulacion (text)',
//         'fecha_nacimiento (date)',
//         'nacionalidad (text)',
//         'provincia_reside (text)',
//         'canton_reside (text)',
//         'parroquia_reside (text)',
//         'instancia_postulacion (text)',
//         'instancia_de_asignacion (text)',
//         'gratuidad (text)',
//         'observacion_gratuidad (text)',
//     ];
//     return Excel::download(new class($headers) implements FromArray, WithHeadings {
//         use Exportable;
    
//         private $headers;
    
//         public function __construct(array $headers)
//         {
//             $this->headers = $headers;
//         }
    
//         public function array(): array
//         {
//             return [
//                 $this->headers
//             ];
//         }
    
//         public function headings(): array
//         {
//             return $this->headers;
//         }
//     }, 'legalizacion_matricula_template.xlsx');
// }

// // subir excel con la data de los asignados para que se matriculen

// public function upload(Request $request)
// {
//     $request->validate([
//         'file' => 'required|mimes:xlsx,xls'
//     ]);

//     $file = $request->file('file');
//     Excel::import(new class implements ToModel, WithStartRow {
//         use Importable;

//         public function model(array $row)
//         {
//             return new CpuLegalizacionMatricula([
//                 'id_periodo' => $row[0],
//                 'id_registro_nacional' => $row[1],
//                 'id_postulacion' => $row[2],
//                 'ciudad_campus' => $row[3],
//                 'id_sede' => $row[4],
//                 'id_facultad' => $row[5],
//                 'id_carrera' => $row[6],
//                 'email' => $row[7],
//                 'cedula' => $row[8],
//                 'apellidos' => $row[9],
//                 'nombres' => $row[10],
//                 'genero' => $row[11],
//                 'etnia' => $row[12],
//                 'discapacidad' => $row[13],
//                 'segmento_persona' => $row[14],
//                 'nota_postulacion' => $row[15],
//                 'fecha_nacimiento' => $row[16],
//                 'nacionalidad' => $row[17],
//                 'provincia_reside' => $row[18],
//                 'canton_reside' => $row[19],
//                 'parroquia_reside' => $row[20],
//                 'instancia_postulacion' => $row[21],
//                 'instancia_de_asignacion' => $row[22],
//                 'gratuidad' => $row[23],
//                 'observacion_gratuidad' => $row[24],
//             ]);
//         }

//         public function startRow(): int
//         {
//             return 2; // Omitir la primera fila (encabezados)
//         }
//     }, $file);

//     return response()->json(['message' => 'Archivo cargado exitosamente']);
// }

}
