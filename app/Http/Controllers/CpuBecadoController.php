<?php

namespace App\Http\Controllers;

use App\Models\CpuBecado;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as ReaderXlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;

class CpuBecadoController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function consultarPorIdentificacionYPeriodo($identificacion, $periodo)
    {
        $becado = CpuBecado::where('identificacion', $identificacion)
                            ->where('periodo', $periodo)
                            ->select('identificacion', 'periodo', 'nombres', 'apellidos', 'sexo', 'tipo_beca_otorgada', 'beca', 'monto_otorgado','monto_consumido', 'fecha_aprobacion_denegacion')
                            ->first();

        if ($becado) {
            return response()->json($becado);
        }

        return response()->json(['message' => 'No se encontró el o la estudiante'], 404);
    }

    public function importarExcel(Request $request)
{
    $request->validate([
        'archivo' => 'required|mimes:xls,xlsx',
    ]);

    try {
        $imported = 0;
        $errors = [];

        $file = $request->file('archivo');

        $reader = IOFactory::createReader('Xlsx');
        $spreadsheet = $reader->load($file->path());
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        $firstRow = true; // Variable para controlar la primera fila
        foreach ($rows as $key => $row) {
            if ($firstRow) {
                $firstRow = false;
                continue; // Saltar la primera fila
            }

            try {
                CpuBecado::create([
                    'periodo' => $row[0],
                    'identificacion' => $row[1],
                    'nombres' => $row[2],
                    'apellidos' => $row[3],
                    'sexo' => $row[4],
                    'email' => $row[5],
                    'telefono' => $row[6],
                    'beca' => $row[7],
                    'tipo_beca_otorgada' => $row[8],
                    'monto_otorgado' => is_numeric(str_replace(',', '.', $row[9])) ? number_format(floatval(str_replace(',', '.', $row[9])), 2, '.', '') : null,
                    'porcentaje_valor_arancel' => $row[10],
                    'estado_postulante' => $row[11],
                    'fecha_aprobacion_denegacion' => $row[12],
                    'datos_bancarios_institucion_bancaria' => $row[13] ?? null,
                    'datos_bancarios_tipo_cuenta' => $row[14] ?? null,
                    'datos_bancarios_numero_cuenta' => $row[15] ?? null,
                    'promedio_dos_periodos_anteriores' => $row[16] ?? null,
                    'fecha_nacimiento' => $row[17] ?? null,
                    'estado_civil' => $row[18] ?? null,
                    'tipo_sangre' => $row[19] ?? null,
                    'ciudad_nacimiento' => $row[20] ?? null,
                    'direccion_residencia' => $row[21] ?? null,
                    'discapacidad' => $row[22] ?? null,
                    'tipo_discapacidad' => $row[23] ?? null,
                    'porcentaje_discapacidad' => $row[24] ?? null,
                    'numero_carnet_discapacidad' => $row[25] ?? null,
                    'matriz_extension' => $row[26] ?? null,
                    'facultad' => $row[27] ?? null,
                    'carrera' => $row[28] ?? null,
                    'carrera_codigo_senescyt' => $row[29] ?? null,
                    'curso_semestre' => $row[30] ?? null,
                    'numero_matricula' => $row[31] ?? null,
                ]);
                $imported++;
            } catch (\Exception $e) {
                // Guardar información del error
                $errors[] = [
                    'fila' => $key + 1,
                    'error' => $e->getMessage(),
                ];
            }
        }

        if (!empty($errors)) {
            return response()->json(['message' => 'Hubo errores durante la importación', 'imported' => $imported, 'errors' => $errors], 422);
        } else {
            return response()->json(['message' => 'Datos importados correctamente', 'imported' => $imported, 'errors' => $errors], 200);
        }
    } catch (\Exception $e) {
        return response()->json(['message' => 'Error en la importación: ' . $e->getMessage(), 'errors' => 1], 422);
    }
}



}
