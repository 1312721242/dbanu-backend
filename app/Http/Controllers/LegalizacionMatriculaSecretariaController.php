<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as ReaderXlsx;
use App\Models\CpuLegalizacionMatricula;
use Symfony\Component\HttpFoundation\Response;
use PhpOffice\PhpSpreadsheet\Shared\Date;


class LegalizacionMatriculaSecretariaController extends Controller
{
    public function exportTemplate()
{
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    $headers = [
        'id_periodo (integer)',
        'id_registro_nacional (text)',
        'id_postulacion (integer)',
        'ciudad_campus (text)',
        'id_sede (integer)',
        'id_facultad (integer)',
        'id_carrera (integer)',
        'email (text)',
        'cedula (text)',
        'apellidos (text)',
        'nombres (text)',
        'genero (text)',
        'etnia (text)',
        'discapacidad (text)',
        'segmento_persona (text)',
        'nota_postulacion (text)',
        'fecha_nacimiento (date)',
        'nacionalidad (text)',
        'provincia_reside (text)',
        'canton_reside (text)',
        'parroquia_reside (text)',
        'instancia_postulacion (text)',
        'instancia_de_asignacion (text)',
        'gratuidad (text)',
        'observacion_gratuidad (text)',
    ];

    // Set headers
    $column = 'A';
    foreach ($headers as $header) {
        $sheet->setCellValue($column . '1', $header);
        $column++;
    }

    $writer = new Xlsx($spreadsheet);
    $filename = 'legalizacion_matricula_template.xlsx';
    $writer->save($filename);

    // Devolver la respuesta de descarga y eliminar el archivo después de enviarlo
    return response()->download($filename)->deleteFileAfterSend(true);
}


// public function upload(Request $request)
// {
//     $request->validate([
//         'file' => 'required|mimes:xlsx,xls'
//     ]);

//     $file = $request->file('file');

//     // Cargar el archivo usando PhpSpreadsheet
//     $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
//     $spreadsheet = $reader->load($file->getRealPath());

//     $sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);

//     $firstRow = true; // Variable para controlar la primera fila
//     foreach ($sheetData as $key => $row) {
//         if ($firstRow) {
//             $firstRow = false;
//             continue; // Saltar la primera fila
//         }

//         $data = [
//             'id_periodo' => $row['A'] ?? null,
//             'id_registro_nacional' => $row['B'] ?? null,
//             'id_postulacion' => $row['C'] ?? null,
//             'ciudad_campus' => $row['D'] ?? null,
//             'id_sede' => $row['E'] ?? null,
//             'id_facultad' => $row['F'] ?? null,
//             'id_carrera' => $row['G'] ?? null,
//             'email' => $row['H'] ?? null,
//             'cedula' => $row['I'] ?? null,
//             'apellidos' => $row['J'] ?? null,
//             'nombres' => $row['K'] ?? null,
//             'genero' => $row['L'] ?? null,
//             'etnia' => $row['M'] ?? null,
//             'discapacidad' => $row['N'] ?? null,
//             'segmento_persona' => $row['O'] ?? null,
//             'nota_postulacion' => $row['P'] ?? null,
//             'fecha_nacimiento' => null, // Asignar null al campo fecha_nacimiento
//             'nacionalidad' => $row['R'] ?? null,
//             'provincia_reside' => $row['S'] ?? null,
//             'canton_reside' => $row['T'] ?? null,
//             'parroquia_reside' => $row['U'] ?? null,
//             'instancia_postulacion' => $row['V'] ?? null,
//             'instancia_de_asignacion' => $row['W'] ?? null,
//             'gratuidad' => $row['X'] ?? null,
//             'observacion_gratuidad' => $row['Y'] ?? null,
//         ];

//         // Check if record already exists
//         if ($data['id_periodo'] && $data['cedula']) {
//             $existingRecord = CpuLegalizacionMatricula::where('id_periodo', $data['id_periodo'])
//                 ->where('cedula', $data['cedula'])
//                 ->exists();

//             if (!$existingRecord) {
//                 $model = new CpuLegalizacionMatricula();
//                 $model->fill($data);
//                 $model->save();
//             }
//         }
//     }

//     return response()->json(['message' => 'Archivo cargado exitosamente']);
// }

public function upload(Request $request, $id_periodo)
{
    $request->validate([
        'file' => 'required|mimes:xlsx,xls'
    ]);

    $file = $request->file('file');

    // Cargar el archivo usando PhpSpreadsheet
    $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
    $spreadsheet = $reader->load($file->getRealPath());

    $sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);

    $firstRow = true; // Variable para controlar la primera fila
    foreach ($sheetData as $key => $row) {
        if ($firstRow) {
            $firstRow = false;
            continue; // Saltar la primera fila
        }

        $data = [
            'id_periodo' => $id_periodo,
            'id_registro_nacional' => $row['B'] ?? null,
            'id_postulacion' => $row['C'] ?? null,
            'ciudad_campus' => $row['D'] ?? null,
            'id_sede' => $row['E'] ?? null,
            'id_facultad' => $row['F'] ?? null,
            'id_carrera' => $row['G'] ?? null,
            'email' => $row['H'] ?? null,
            'cedula' => $row['I'] ?? null,
            'apellidos' => $row['J'] ?? null,
            'nombres' => $row['K'] ?? null,
            'genero' => $row['L'] ?? null,
            'etnia' => $row['M'] ?? null,
            'discapacidad' => $row['N'] ?? null,
            'segmento_persona' => $row['O'] ?? null,
            'nota_postulacion' => $row['P'] ?? null,
            'fecha_nacimiento' => null, // Asignar null al campo fecha_nacimiento
            'nacionalidad' => $row['R'] ?? null,
            'provincia_reside' => $row['S'] ?? null,
            'canton_reside' => $row['T'] ?? null,
            'parroquia_reside' => $row['U'] ?? null,
            'instancia_postulacion' => $row['V'] ?? null,
            'instancia_de_asignacion' => $row['W'] ?? null,
            'gratuidad' => $row['X'] ?? null,
            'observacion_gratuidad' => $row['Y'] ?? null,
        ];

        // Check if record already exists
        if ($data['id_periodo'] && $data['cedula']) {
            $existingRecord = CpuLegalizacionMatricula::where('id_periodo', $data['id_periodo'])
                ->where('cedula', $data['cedula'])
                ->exists();

            if (!$existingRecord) {
                $model = new CpuLegalizacionMatricula();
                $model->fill($data);
                $model->save();
            }
        }
    }

    return response()->json(['message' => 'Archivo cargado exitosamente']);
}


 

}
