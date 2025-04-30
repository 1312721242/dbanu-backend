<?php

namespace App\Http\Controllers;

use App\Models\CpuBecado;
use App\Models\CpuClientesTasty;
use App\Models\CpuConsumoBecado;
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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class CpuBecadoController extends Controller
{


    public function consultarPorIdentificacionYPeriodo($identificacion, $periodo)
    {
        $becado = CpuBecado::where('identificacion', $identificacion)
            ->where('periodo', $periodo)
            ->select('id', 'identificacion', 'periodo', 'nombres', 'apellidos', 'sexo', 'tipo_beca_otorgada', 'beca', 'monto_otorgado', 'monto_consumido', 'fecha_aprobacion_denegacion')
            ->first();

        if ($becado) {
            return response()->json($becado);
        }

        // Auditoría
        $this->auditar('cpu_becado', 'identificacion', '', '', 'CONSULTA', "CONSULTA DE BECADOS POR IDENTIFICACION Y PERIODO: {$identificacion}, {$periodo}");

        return response()->json(['message' => 'No se encontró el o la estudiante'], 404);
    }

    // public function consultarPorCodigoTarjeta($codigoTarjeta)
    // {
    //     $currentDate = Carbon::now()->toDateString(); // Obtener solo la parte de la fecha (YYYY-MM-DD)

    //     // Buscar en CpuBecado
    //     $becado = CpuBecado::where('codigo_tarjeta', $codigoTarjeta)
    //         ->where('fecha_inicio_valido', '<=', $currentDate)
    //         ->where('fecha_fin_valido', '>=', $currentDate)
    //         ->where('id_estado', 8)
    //         ->select('id', 'identificacion', 'periodo', 'nombres', 'apellidos', 'sexo', 'email', 'telefono', 'beca', 'tipo_beca_otorgada', 'monto_otorgado', 'monto_consumido', 'codigo_tarjeta', 'fecha_inicio_valido', 'fecha_fin_valido', 'carrera', 'id_estado')
    //         ->first();

    //     if ($becado) {
    //         // Obtener el valor consumido en la fecha actual
    //         $valorConsumidoHoy = CpuConsumoBecado::where('id_becado', $becado->id)
    //             ->whereDate('created_at', $currentDate)
    //             ->sum('monto_facturado');

    //         // Log::info('Registro encontrado en CpuBecado');

    //         // Auditoría
    //         $this->auditar('cpu_becado', 'identificacion', '', '', 'CONSULTA', "CONSULTA DE BECADOS POR CODIGO DE TARJETA: {$codigoTarjeta}");

    //         return response()->json([
    //             'becado' => $becado,
    //             'valor_consumido_hoy' => $valorConsumidoHoy,
    //             'valor_consumido_total' => $becado->monto_consumido
    //         ]);
    //     }

    //     // Si no se encuentra en CpuBecado, buscar en CpuClientesTasty (cpu_funcionario_comunidad)
    //     // Log::info('Buscando en CpuClientesTasty (cpu_funcionario_comunidad)');

    //     $funcionario = CpuClientesTasty::where('codigo_tarjeta', $codigoTarjeta)
    //         ->where('fecha_inicio_valido', '<=', $currentDate)
    //         ->where('fecha_fin_valido', '>=', $currentDate)
    //         ->where('id_estado', 8)
    //         ->select('id', 'identificacion', 'nombres', 'apellidos', 'email', 'telefono', 'unidad_facultad_direccion', 'cargo_puesto', 'codigo_tarjeta', 'fecha_inicio_valido', 'fecha_fin_valido')
    //         ->first();

    //     if ($funcionario) {
    //         // Log::info('Registro encontrado en CpuClientesTasty (cpu_funcionario_comunidad)');

    //         return response()->json([
    //             'funcionario' => $funcionario
    //         ]);
    //     }

    //     // Log::info('No se encontró un registro válido para el código de tarjeta');

    //     return response()->json(['message' => 'No se encontró un registro válido para el código de tarjeta'], 204);
    // }


    public function consultarPorCodigoTarjeta($codigo)
    {
        $currentDate = Carbon::now()->toDateString();
        $tipoIdentificacion = preg_match('/^00+/', $codigo) ? 'T' : 'C';
        // Log::info("🔎 Iniciando búsqueda local para código: {$codigo} (Tipo: {$tipoIdentificacion})");

        // 1. Buscar primero en cpu_becado
        $becado = CpuBecado::where('codigo_tarjeta', $codigo)
            ->where('fecha_inicio_valido', '<=', $currentDate)
            ->where('fecha_fin_valido', '>=', $currentDate)
            ->where('id_estado', 8)
            ->first();

        if ($becado) {
            $valorConsumidoHoy = CpuConsumoBecado::where('id_becado', $becado->id)
                ->whereDate('created_at', $currentDate)
                ->sum('monto_facturado');

            // Log::info("✅ Usuario encontrado en tabla cpu_becado: {$becado->identificacion}");
            return response()->json([
                'becado' => $becado,
                'valor_consumido_hoy' => $valorConsumidoHoy,
                'valor_consumido_total' => $becado->monto_consumido
            ]);
        }

        // 2. Buscar en cpu_funcionario_comunidad
        $funcionario = CpuClientesTasty::where('codigo_tarjeta', $codigo)->first();
        if ($funcionario) {
            $diasSinActualizar = Carbon::parse($funcionario->updated_at)->diffInDays(now());
            if ($diasSinActualizar <= 30) {
                Log::info("📌 Funcionario encontrado y actualizado recientemente ({$diasSinActualizar} días): {$funcionario->identificacion}");
                return response()->json(['funcionario' => $funcionario]);
            }
            // Log::info("⏳ Funcionario encontrado pero con datos antiguos ({$diasSinActualizar} días), se consultará la API.");
        } else {
            // Log::info("ℹ No se encontró en cpu_funcionario_comunidad localmente. Se consultará la API.");
        }

        // 3. Obtener token de Azure
        try {
            $tokenResponse = Http::asForm()->post('https://login.microsoftonline.com/31a17900-7589-4cfc-b11a-f4e83c27b8ed/oauth2/v2.0/token', [
                'grant_type' => 'client_credentials',
                'client_id' => '06772b06-a35c-4240-ac76-cb457f1314e4',
                'client_secret' => 'uBL8Q~XKyGvLkM1~sVL14-UHgIXV-BHo-7p9adgt',
                'scope' => 'https://service.flow.microsoft.com//.default'
            ]);

            if ($tokenResponse->failed()) {
                // Log::error("❌ Error al obtener token de Azure: " . $tokenResponse->body());
                return response()->json(['error' => 'Error al autenticar con Azure'], 500);
            }

            $accessToken = $tokenResponse->json()['access_token'];
            // Log::info("🔐 Token de acceso obtenido correctamente.");
        } catch (\Exception $e) {
            // Log::error("❌ Excepción al obtener token Azure: " . $e->getMessage());
            return response()->json(['error' => 'Error al autenticar con Azure'], 500);
        }

        // 4. Consultar API externa
        try {
            $url = "https://prod-181.westus.logic.azure.com/workflows/c7da91845e08486f8e746efea7e72fe5/triggers/manual/paths/invoke/id/{$codigo}/{$tipoIdentificacion}?api-version=2016-06-01";
            $azureResponse = Http::withHeaders([
                'Authorization' => "Bearer {$accessToken}"
            ])->get($url);

            // Log::info("🌐 Llamada a API externa: {$url}");
            // Log::info("📥 Respuesta Azure: " . $azureResponse->status() . ' - ' . $azureResponse->body());

            if ($azureResponse->status() === 204) {
                // Log::warning("⚠ No se encontró información en la API externa para {$codigo}.");
                return response()->json(['message' => 'No se encontró información en la API externa.'], 204);
            }

            if ($azureResponse->failed()) {
                // Log::error("❌ Error al consultar API externa: " . $azureResponse->body());
                return response()->json(['error' => 'Error al consultar API externa.'], 500);
            }

            $data = $azureResponse->json();
            $identificacion = $data['identificacion'];

            $datosComunes = [
                'identificacion' => $identificacion,
                'nombres' => $data['nombres'],
                'apellidos' => $data['apellidos'],
                'email' => $data['correo'],
                'unidad_facultad_direccion' => $data['unidadOrganizativaAbreviatura'],
                'cargo_puesto' => $data['tipoUsuario'],
                'codigo_tarjeta' => $data['numeroTarjeta'],
                'updated_at' => now(),
                'fecha_inicio_valido' => now(),
                'fecha_fin_valido' => now()->addYear(),
                'id_estado' => 8
            ];

            $funcionarioExistente = CpuClientesTasty::where('identificacion', $identificacion)->first();

            if ($funcionarioExistente) {
                $funcionarioExistente->update($datosComunes);
                // Log::info("🛠 Datos actualizados en cpu_funcionario_comunidad para ID: {$funcionarioExistente->id}");
            } else {
                $funcionarioExistente = CpuClientesTasty::create($datosComunes);
                // Log::info("🆕 Nuevo registro creado en cpu_funcionario_comunidad con ID: {$funcionarioExistente->id}");
            }

            return response()->json(['funcionario' => $funcionarioExistente]);
        } catch (\Exception $e) {
            Log::error("❌ Excepción en la lógica de consulta o inserción: " . $e->getMessage());
            return response()->json(['error' => 'Error inesperado en la consulta'], 500);
        }
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

            // Auditoría
            $this->auditar('cpu_becado', 'identificacion', '', '', 'INSERCION', "IMPORTACION DE BECADOS POR EXCEL");
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error en la importación: ' . $e->getMessage(), 'errors' => 1], 422);
        }
    }

    public function generarQRCode($identificacion, $periodo)
    {
        $contenidoQR = $identificacion . '-' . $periodo;

        // Generar el código QR y guardarlo en una variable
        $qrCode = QrCode::format('png')->size(300)->generate($contenidoQR);

        // Auditoría
        $this->auditar('cpu_becado', 'identificacion', '', '', 'GENERACION', "GENERACION DE QR CODE PARA BECADOS: {$identificacion}, {$periodo}");

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

        // Auditoría
        $this->auditar('cpu_becado', 'identificacion', '', '', 'GENERACION', "GENERACION DE PDF DE CREDENCIAL PARA BECADOS: {$identificacion}, {$periodo}");

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

        // Auditoría
        $this->auditar('cpu_becado', 'identificacion', '', '', 'GENERACION', "GENERACION DE QR CODE PARA BECADOS: {$identificacion}, {$periodo}");

        // Devolver la ruta del archivo temporal
        return $tempFilePath;
    }

    public function index(Request $request)
    {
        $queryBecados = CpuBecado::query();
        $queryClientesTasty = CpuClientesTasty::query();

        // Aplicar filtros si existen
        if ($request->has('identificacion') && $request->input('identificacion') != '') {
            $queryBecados->where('identificacion', 'like', '%' . $request->input('identificacion') . '%');
            $queryClientesTasty->where('identificacion', 'like', '%' . $request->input('identificacion') . '%');
        }

        if ($request->has('nombres') && $request->input('nombres') != '') {
            $queryBecados->where('nombres', 'like', '%' . $request->input('nombres') . '%');
            $queryClientesTasty->where('nombres', 'like', '%' . $request->input('nombres') . '%');
        }

        if ($request->has('apellidos') && $request->input('apellidos') != '') {
            $queryBecados->where('apellidos', 'like', '%' . $request->input('apellidos') . '%');
            $queryClientesTasty->where('apellidos', 'like', '%' . $request->input('apellidos') . '%');
        }

        // Recuperar todos los campos de cada modelo
        $becados = $queryBecados->get();
        $clientesTasty = $queryClientesTasty->get();

        // Combinar las dos colecciones
        $combinedResults = $becados->concat($clientesTasty);

        // Auditoría
        $this->auditar('cpu_becado', 'identificacion', '', '', 'CONSULTA', "CONSULTA DE TODOS LOS BECADOS");

        return response()->json($combinedResults);
    }

    public function actualizarCodigoTarjeta(Request $request, $identificacion)
    {
        $request->validate([
            'codigo_tarjeta' => [
                'required',
                'string',
                'max:255',
                Rule::unique('cpu_becados', 'codigo_tarjeta')->where(function ($query) use ($identificacion) {
                    return $query->where('identificacion', $identificacion);
                }),
                Rule::unique('cpu_funcionario_comunidad', 'codigo_tarjeta')->where(function ($query) use ($identificacion) {
                    return $query->where('identificacion', $identificacion);
                }),
            ],
        ]);

        // Intentar encontrar el becado por identificación primero
        // $becado = CpuBecado::where('identificacion', $identificacion)->first();
        $becado = CpuBecado::where('id', $identificacion)->first();

        if ($becado) {
            $becado->codigo_tarjeta = $request->input('codigo_tarjeta');
            $becado->save();
            return response()->json(['message' => 'Código de tarjeta actualizado correctamente en CpuBecado', 'becado' => $becado], 200);
        }

        // Si no se encuentra en CpuBecado, buscar en CpuClientesTasty
        $clienteTasty = CpuClientesTasty::where('identificacion', $identificacion)->first();
        if ($clienteTasty) {
            $clienteTasty->codigo_tarjeta = $request->input('codigo_tarjeta');
            $clienteTasty->save();
            return response()->json(['message' => 'Código de tarjeta actualizado correctamente en CpuFuncionarioComunidad', 'cliente' => $clienteTasty], 200);
        }

        // Auditoría
        $this->auditar('cpu_becado', 'identificacion', '', '', 'MODIFICACION', "ACTUALIZACION DE CODIGO DE TARJETA PARA BECADOS: {$identificacion}, {$request->input('codigo_tarjeta')}");

        // Si no se encuentra en ninguna tabla
        return response()->json(['message' => 'No se encontró el registro con esa identificación'], 404);
    }

    //funcion para auditar
    private function auditar($tabla, $campo, $dataOld, $dataNew, $tipo, $descripcion, $request = null)
    {
        $usuario = $request && !is_string($request) ? $request->user()->name : auth()->user()->name;
        $ip = $request && !is_string($request) ? $request->ip() : request()->ip();
        $ipv4 = gethostbyname(gethostname());
        $publicIp = file_get_contents('http://ipecho.net/plain');
        $ioConcatenadas = 'IP LOCAL: ' . $ip . '  --IPV4: ' . $ipv4 . '  --IP PUBLICA: ' . $publicIp;
        $nombreequipo = gethostbyaddr($ip);
        $userAgent = $request && !is_string($request) ? $request->header('User-Agent') : request()->header('User-Agent');
        $tipoEquipo = 'Desconocido';

        if (stripos($userAgent, 'Mobile') !== false) {
            $tipoEquipo = 'Celular';
        } elseif (stripos($userAgent, 'Tablet') !== false) {
            $tipoEquipo = 'Tablet';
        } elseif (stripos($userAgent, 'Laptop') !== false || stripos($userAgent, 'Macintosh') !== false) {
            $tipoEquipo = 'Laptop';
        } elseif (stripos($userAgent, 'Windows') !== false || stripos($userAgent, 'Linux') !== false) {
            $tipoEquipo = 'Computador de Escritorio';
        }
        $nombreUsuarioEquipo = get_current_user() . ' en ' . $tipoEquipo;

        $fecha = now();
        $codigo_auditoria = strtoupper($tabla . '_' . $campo . '_' . $tipo);
        DB::table('cpu_auditoria')->insert([
            'aud_user' => $usuario,
            'aud_tabla' => $tabla,
            'aud_campo' => $campo,
            'aud_dataold' => $dataOld,
            'aud_datanew' => $dataNew,
            'aud_tipo' => $tipo,
            'aud_fecha' => $fecha,
            'aud_ip' => $ioConcatenadas,
            'aud_tipoauditoria' => $this->getTipoAuditoria($tipo),
            'aud_descripcion' => $descripcion,
            'aud_nombreequipo' => $nombreequipo,
            'aud_descrequipo' => $nombreUsuarioEquipo,
            'aud_codigo' => $codigo_auditoria,
            'created_at' => now(),
            'updated_at' => now(),

        ]);
    }

    private function getTipoAuditoria($tipo)
    {
        switch ($tipo) {
            case 'CONSULTA':
                return 1;
            case 'INSERCION':
                return 3;
            case 'MODIFICACION':
                return 2;
            case 'ELIMINACION':
                return 4;
            case 'LOGIN':
                return 5;
            case 'LOGOUT':
                return 6;
            case 'DESACTIVACION':
                return 7;
            default:
                return 0;
        }
    }
}
