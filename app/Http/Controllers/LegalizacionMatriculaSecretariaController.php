<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as ReaderXlsx;
use App\Models\CpuLegalizacionMatricula;
use Symfony\Component\HttpFoundation\Response;

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

public function upload(Request $request)
{
    $request->validate([
        'file' => 'required|mimes:xlsx,xls'
    ]);

    $file = $request->file('file');
    $reader = new ReaderXlsx();
    $spreadsheet = $reader->load($file);

    $sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);

    foreach ($sheetData as $key => $row) {
        if ($key === 1) continue; // Skip header row

        $data = [];
        foreach ($row as $cell) {
            $data[] = $cell;
        }

        // Check if record already exists
        $existingRecord = CpuLegalizacionMatricula::where('id_periodo', $data[0])
            ->where('cedula', $data[8])
            ->exists();

        if (!$existingRecord) {
            $model = new CpuLegalizacionMatricula();
            $model->fill($data);
            $model->save();
        }
    }

    return response()->json(['message' => 'Archivo cargado exitosamente']);
}

}
