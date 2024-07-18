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
use Dompdf\Dompdf;
use Dompdf\Options;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Endroid\QrCode\QrCode as EndroidQrCode;
use Carbon\Carbon; // Asegúrate de importar Carbon

class CpuBecadoController extends Controller
{
    // public function __construct()
    // {
    //     $this->middleware('auth:api');
    // }

    public function consultarPorIdentificacionYPeriodo($identificacion, $periodo)
    {
        $becado = CpuBecado::where('identificacion', $identificacion)
            ->where('periodo', $periodo)
            ->select('id', 'identificacion', 'periodo', 'nombres', 'apellidos', 'sexo', 'tipo_beca_otorgada', 'beca', 'monto_otorgado', 'monto_consumido', 'fecha_aprobacion_denegacion')
            ->first();

        if ($becado) {
            return response()->json($becado);
        }

        return response()->json(['message' => 'No se encontró el o la estudiante'], 404);
    }

    public function consultarPorCodigoTarjeta($codigoTarjeta)
    {
        $currentDate = Carbon::now()->toDateString(); // Obtener solo la parte de la fecha (YYYY-MM-DD)
    
        $becado = CpuBecado::where('codigo_tarjeta', $codigoTarjeta)
            ->where('fecha_inicio_valido', '<=', $currentDate)
            ->where('fecha_fin_valido', '>=', $currentDate)
            ->select('id', 'identificacion', 'periodo', 'nombres', 'apellidos', 'sexo', 'email', 'telefono', 'beca', 'tipo_beca_otorgada', 'monto_otorgado', 'monto_consumido', 'codigo_tarjeta', 'fecha_inicio_valido', 'fecha_fin_valido', 'carrera')
            ->first();
    
        if ($becado) {
            // Obtener el valor consumido en la fecha actual
            $valorConsumidoHoy = CpuConsumoBecado::where('becado_id', $becado->id)
                ->whereDate('created_at', $currentDate)
                ->sum('monto_facturado');
    
            return response()->json([
                'becado' => $becado,
                'valor_consumido_hoy' => $valorConsumidoHoy,
                'valor_consumido_total' => $becado->monto_consumido
            ]);
        }
    
        return response()->json(['message' => 'No se encontró un registro válido para el código de tarjeta'], 404);
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

    public function generarQRCode($identificacion, $periodo)
    {
        $contenidoQR = $identificacion . '-' . $periodo;

        // Generar el código QR y guardarlo en una variable
        $qrCode = QrCode::format('png')->size(300)->generate($contenidoQR);

        // Devolver el código QR como una respuesta de tipo imagen PNG
        return response($qrCode)->header('Content-Type', 'image/png');
    }

    public function generarCredencialPDF($identificacion, $periodo)
    {
        $becado = CpuBecado::where('identificacion', $identificacion)
            ->where('periodo', $periodo)
            ->first();

        if (!$becado) {
            return response()->json(['message' => 'No se encontró el o la estudiante'], 404);
        }

        // Crear una nueva instancia de Dompdf
        $dompdf = new Dompdf();

        // Opciones para personalizar Dompdf (opcional)
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', true);

        // Crear el contenido HTML del PDF directamente en una cadena
        $html = '<!DOCTYPE html>
                <html lang="es">
                <head>
                    <meta charset="UTF-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                    <title>Gafete</title>
                    <style>
                        /* Estilos generales */
                        body {
                            font-family: Arial, sans-serif;
                            margin: 0;
                            padding: 0;
                        }

                        .gafete {
                            width: 200px; /* Ajusta el ancho del gafete */
                            height: 100px; /* Ajusta la altura del gafete */
                            background-color: #fff;
                            border: 1px solid #ccc;
                            padding: 10px;
                            text-align: center;
                        }

                        .logo {
                            width: 100px; /* Ajusta el ancho del logo */
                            height: auto; /* Conserva la proporción de altura del logo */
                            margin-bottom: 10px;
                        }

                        .nombre {
                            font-size: 16px; /* Ajusta el tamaño de la fuente del nombre */
                            font-weight: bold;
                        }

                        .cargo {
                            font-size: 12px; /* Ajusta el tamaño de la fuente del cargo */
                        }

                        .slogan {
                            font-size: 10px; /* Ajusta el tamaño de la fuente del slogan */
                            margin-top: 10px;
                        }

                        .qr-code {
                            margin-top: 10px;
                        }
                    </style>
                </head>
                <body>
                    <div class="gafete">
                        <img src="logo.png" alt="Logo" class="logo">
                        <div class="informacion">
                            <p class="nombre">' . $becado->nombres . ' ' . $becado->apellidos . '</p>
                            <p class="cargo">' . $becado->cargo . '</p>
                            <p class="slogan">' . $becado->slogan . '</p>
                        </div>
                        <div class="qr-code">
                            <img src="' . $this->generarQR($becado->identificacion, $becado->periodo) . '" alt="QR Code">
                        </div>
                    </div>
                </body>
                </html>';

        // Cargar el contenido HTML en Dompdf
        $dompdf->loadHtml($html);

        // Renderizar el PDF
        $dompdf->render();

        // Devolver el PDF como una respuesta de tipo application/pdf
        return $dompdf->stream('credencial.pdf');
    }

    public function generarQR($identificacion, $periodo)
    {
        $qrText = $identificacion . '-' . $periodo;
        // $qrCode = new EndroidQrCode($qrText);
        // Crear una instancia de QrCode
        $qrCode = new QrCode($qrText);

        // Generar el código QR
        $qrCode = QrCode::format('png')->size(200)->generate($qrText);

        // Guardar el código QR en un archivo
        file_put_contents('ruta/del/archivo.png', $qrCode);

        // Guardar el QR code en un archivo temporal
        $tempFilePath = storage_path('app/temp/qr_code.png');
        $qrCode->writeFile($tempFilePath);

        // Devolver la ruta del archivo temporal
        return $tempFilePath;
    }

    public function index(Request $request)
    {
        $query = CpuBecado::query();

        if ($request->has('identificacion') && $request->input('identificacion') != '') {
            $query->where('identificacion', 'like', '%' . $request->input('identificacion') . '%');
        }

        if ($request->has('nombres') && $request->input('nombres') != '') {
            $query->where('nombres', 'like', '%' . $request->input('nombres') . '%');
        }

        if ($request->has('apellidos') && $request->input('apellidos') != '') {
            $query->where('apellidos', 'like', '%' . $request->input('apellidos') . '%');
        }

        $results = $query->get();

        return response()->json($results);
    }

    public function actualizarCodigoTarjeta(Request $request, $id)
    {
        $request->validate([
            'codigo_tarjeta' => 'required|string|max:255|unique:cpu_becados,codigo_tarjeta,' . $id,
        ]);

        $becado = CpuBecado::find($id);

        if (!$becado) {
            return response()->json(['message' => 'No se encontró el registro del estudiante'], 404);
        }

        $becado->codigo_tarjeta = $request->input('codigo_tarjeta');
        $becado->save();

        return response()->json(['message' => 'Código de tarjeta actualizado correctamente', 'becado' => $becado], 200);
    }
}
