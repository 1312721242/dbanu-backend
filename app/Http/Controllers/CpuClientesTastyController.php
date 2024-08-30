<?php

namespace App\Http\Controllers;

use App\Models\CpuBecado;
use App\Models\CpuClientesTasty;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class CpuClientesTastyController extends Controller
{
    // Método para exportar la plantilla
    public function exportClientesTastyTemplate(Request $request)
    {
        // Obtener el parámetro 'tipoBeneficiario' de la solicitud
        $tipoBeneficiario = $request->query('tipoBeneficiario');

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        if ($tipoBeneficiario === 'Personal Uleam/Otro') {
            // Definir los encabezados para Personal Uleam/Otro
            $headers = [
                'identificacion (text)',
                'nombres (text)',
                'apellidos (text)',
                'email (text)',
                'telefono (text)',
                'unidad_facultad_direccion (text)',
                'cargo_puesto (text)',
                'codigo_tarjeta (text)',
            ];
            $filename = 'funcionario_comunidad_template.xlsx';
        } elseif ($tipoBeneficiario === 'Becado') {
            // Definir los encabezados para Becado
            $headers = [
                'periodo',
                'identificacion',
                'nombres',
                'apellidos',
                'sexo',
                'email',
                'telefono',
                'beca',
                'tipo_beca_otorgada',
                'monto_otorgado',
                'porcentaje_valor_arancel',
                'estado_postulante',
                'fecha_aprobacion_denegacion',
                'datos_bancarios_institucion_bancaria',
                'datos_bancarios_tipo_cuenta',
                'datos_bancarios_numero_cuenta',
                'promedio_dos_periodos_anteriores',
                'fecha_nacimiento',
                'estado_civil',
                'tipo_sangre',
                'ciudad_nacimiento',
                'direccion_residencia',
                'discapacidad',
                'tipo_discapacidad',
                'porcentaje_discapacidad',
                'numero_carnet_discapacidad',
                'matriz_extension',
                'facultad',
                'carrera',
                'carrera_codigo_senescyt',
                'curso_semestre',
                'numero_matricula',
                'codigo_tarjeta',
            ];
            $filename = 'becado_template.xlsx';
        } else {
            return response()->json(['error' => 'Tipo de beneficiario no válido'], 400);
        }

        // Establecer los encabezados en la primera fila
        $column = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($column . '1', $header);
            $column++;
        }

        // Configurar columnas específicas como texto
        $columnsAsText = ['A', 'E', 'H']; // A -> identificacion, E -> telefono, H -> codigo_tarjeta

        foreach ($columnsAsText as $col) {
            $sheet->getStyle($col)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT);
        }

        // Generar y guardar el archivo Excel
        $writer = new Xlsx($spreadsheet);
        $writer->save($filename);

        // Devolver la respuesta de descarga y eliminar el archivo después de enviarlo
        return response()->download($filename)->deleteFileAfterSend(true);
    }



    public function uploadClientesTasty(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls',
            'fecha_inicio_valido' => 'required|date',
            'fecha_fin_valido' => 'required|date|after_or_equal:fecha_inicio_valido',
            'tipoBeneficiario' => 'required|string', // Validar el tipo de beneficiario
        ]);

        $file = $request->file('file');
        $fechaInicio = $request->input('fecha_inicio_valido');
        $fechaFin = $request->input('fecha_fin_valido');
        $tipoBeneficiario = $request->input('tipoBeneficiario');

        // Cargar el archivo usando PhpSpreadsheet
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $spreadsheet = $reader->load($file->getRealPath());

        $sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);

        $firstRow = true;
        $insertedCount = 0;
        $omittedCount = 0;
        $omittedReasons = [];

        foreach ($sheetData as $key => $row) {
            if ($firstRow) {
                $firstRow = false;
                continue; // Saltar la primera fila
            }

            if ($tipoBeneficiario === 'Personal Uleam/Otro') {
                // Procesar para la tabla CpuFuncionarioComunidad
                $data = [
                    'identificacion' => $row['A'] ?? null,
                    'nombres' => $row['B'] ?? null,
                    'apellidos' => $row['C'] ?? null,
                    'email' => $row['D'] ?? null,
                    'telefono' => $row['E'] ?? null,
                    'unidad_facultad_direccion' => $row['F'] ?? null,
                    'cargo_puesto' => $row['G'] ?? null,
                    'codigo_tarjeta' => $row['H'] ?? null,
                    'fecha_inicio_valido' => $fechaInicio,
                    'fecha_fin_valido' => $fechaFin,
                    'id_estado' => 8,
                ];

                if ($data['identificacion'] && $data['email']) {
                    $existingRecord = CpuClientesTasty::where('identificacion', $data['identificacion'])
                        ->where('email', $data['email'])
                        ->first();

                    if (!$existingRecord) {
                        $model = new CpuClientesTasty();
                        $model->fill($data);
                        $model->save();
                        $insertedCount++;
                    } else {
                        // Actualizar fechas y estado si es necesario
                        $shouldUpdate = false;
                        if ($existingRecord->fecha_inicio_valido !== $fechaInicio || $existingRecord->fecha_fin_valido !== $fechaFin) {
                            $existingRecord->fecha_inicio_valido = $fechaInicio;
                            $existingRecord->fecha_fin_valido = $fechaFin;
                            $shouldUpdate = true;
                        }
                        if ($existingRecord->id_estado !== 8) {
                            $existingRecord->id_estado = 8;
                            $shouldUpdate = true;
                        }
                        if ($shouldUpdate) {
                            $existingRecord->save();
                            $insertedCount++; // Contar como actualización
                        } else {
                            $omittedCount++;
                            $omittedReasons[] = [
                                'row' => $key,
                                'reason' => 'Registro existente sin cambios en las fechas.'
                            ];
                        }
                    }
                } else {
                    $omittedCount++;
                    $omittedReasons[] = [
                        'row' => $key,
                        'reason' => 'Datos incompletos: identificación o email faltante.'
                    ];
                }
            } elseif ($tipoBeneficiario === 'Becado') {
                // Procesar para la tabla cpu_becados
                $data = [
                    'periodo' => $row['A'] ?? null,
                    'identificacion' => $row['B'] ?? null,
                    'nombres' => $row['C'] ?? null,
                    'apellidos' => $row['D'] ?? null,
                    'sexo' => $row['E'] ?? null,
                    'email' => $row['F'] ?? null,
                    'telefono' => $row['G'] ?? null,
                    'beca' => $row['H'] ?? null,
                    'tipo_beca_otorgada' => $row['I'] ?? null,
                    'monto_otorgado' => $row['J'] ?? null,
                    'porcentaje_valor_arancel' => $row['K'] ?? null,
                    'estado_postulante' => $row['L'] ?? null,
                    'fecha_aprobacion_denegacion' => $row['M'] ?? null,
                    'datos_bancarios_institucion_bancaria' => $row['N'] ?? null,
                    'datos_bancarios_tipo_cuenta' => $row['O'] ?? null,
                    'datos_bancarios_numero_cuenta' => $row['P'] ?? null,
                    'promedio_dos_periodos_anteriores' => $row['Q'] ?? null,
                    'fecha_nacimiento' => $row['R'] ?? null,
                    'estado_civil' => $row['S'] ?? null,
                    'tipo_sangre' => $row['T'] ?? null,
                    'ciudad_nacimiento' => $row['U'] ?? null,
                    'direccion_residencia' => $row['V'] ?? null,
                    'discapacidad' => $row['W'] ?? null,
                    'tipo_discapacidad' => $row['X'] ?? null,
                    'porcentaje_discapacidad' => $row['Y'] ?? null,
                    'numero_carnet_discapacidad' => $row['Z'] ?? null,
                    'matriz_extension' => $row['AA'] ?? null,
                    'facultad' => $row['AB'] ?? null,
                    'carrera' => $row['AC'] ?? null,
                    'carrera_codigo_senescyt' => $row['AD'] ?? null,
                    'curso_semestre' => $row['AE'] ?? null,
                    'numero_matricula' => $row['AF'] ?? null,
                    'codigo_tarjeta' => $row['AG'] ?? null,
                    'fecha_inicio_valido' => $fechaInicio,
                    'fecha_fin_valido' => $fechaFin,
                    'id_estado' => 8,
                ];

                if ($data['identificacion'] && $data['email']) {
                    $existingRecord = CpuBecado::where('identificacion', $data['identificacion'])
                        ->where('periodo', $data['periodo'])
                        ->first();

                    if (!$existingRecord) {
                        $model = new CpuBecado();
                        $model->fill($data);
                        $model->save();
                        $insertedCount++;
                    } else {
                        if ($existingRecord->fecha_inicio_valido !== $fechaInicio || $existingRecord->fecha_fin_valido !== $fechaFin) {
                            $existingRecord->fecha_inicio_valido = $fechaInicio;
                            $existingRecord->fecha_fin_valido = $fechaFin;
                            $existingRecord->save();
                            $insertedCount++; // Contar como actualización
                        } else {
                            $omittedCount++;
                            $omittedReasons[] = [
                                'row' => $key,
                                'reason' => 'Registro existente sin cambios en las fechas.'
                            ];
                        }
                    }
                } else {
                    $omittedCount++;
                    $omittedReasons[] = [
                        'row' => $key,
                        'reason' => 'Datos incompletos: identificación o email faltante.'
                    ];
                }
            }
        }

        return response()->json([
            'message' => 'Archivo cargado exitosamente',
            'inserted' => $insertedCount,
            'omitted' => $omittedCount,
            'omitted_reasons' => $omittedReasons
        ]);
    }

    public function disableClientesTasty(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls',
            'tipoBeneficiario' => 'required|string',
        ]);

        $file = $request->file('file');
        $tipoBeneficiario = $request->input('tipoBeneficiario');

        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $spreadsheet = $reader->load($file->getRealPath());

        $sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);

        $firstRow = true;
        $updatedCount = 0;
        $omittedCount = 0;
        $omittedReasons = [];

        foreach ($sheetData as $key => $row) {
            if ($firstRow) {
                $firstRow = false;
                continue; // Saltar la primera fila
            }

            // Cambiar la estructura de la asignación de datos según el tipo de beneficiario
            if ($tipoBeneficiario === 'Personal Uleam/Otro') {
                $identificacion = $row['A'] ?? null;
                $email = $row['D'] ?? null; // Asegúrate de que estas son las columnas correctas para 'Personal Uleam/Otro'
            } elseif ($tipoBeneficiario === 'Becado') {
                $identificacion = $row['B'] ?? null; // Cambio para coincidir con la estructura de datos de 'Becado'
            }

            if (!$identificacion || ($tipoBeneficiario === 'Personal Uleam/Otro' && !$email)) {
                $omittedCount++;
                $omittedReasons[] = [
                    'row' => $key,
                    'reason' => 'Datos incompletos: identificación o email faltante.'
                ];
                continue;
            }

            $model = null;
            if ($tipoBeneficiario === 'Personal Uleam/Otro') {
                $model = CpuClientesTasty::where('identificacion', $identificacion)
                    ->where('email', $email)
                    ->first();
            } elseif ($tipoBeneficiario === 'Becado') {
                $model = CpuBecado::where('identificacion', $identificacion)
                    ->first();
            }

            if (!$model) {
                $omittedCount++;
                $omittedReasons[] = [
                    'row' => $key,
                    'reason' => 'Registro no encontrado.'
                ];
                continue;
            }

            if ($model->id_estado !== 9) {
                $model->id_estado = 9;
                $model->save();
                $updatedCount++;
            } else {
                $omittedCount++;
                $omittedReasons[] = [
                    'row' => $key,
                    'reason' => 'El estado ya estaba actualizado a 9.'
                ];
            }
        }

        return response()->json([
            'message' => 'Proceso de actualización completado',
            'updated' => $updatedCount,
            'omitted' => $omittedCount,
            'omitted_reasons' => $omittedReasons
        ]);
    }


    public function disableClientesTastyIndividual(Request $request)
    {
        $request->validate([
            'identificacion' => 'required|string',
        ]);

        $identificacion = $request->input('identificacion');

        // Intentar encontrar y actualizar en CpuBecado primero
        $becado = CpuBecado::where('identificacion', $identificacion)->first();
        if ($becado) {
            if ($becado->id_estado !== 9) {
                $becado->id_estado = 9;
                $becado->save();
                return response()->json([
                    'message' => 'Estado actualizado correctamente a 9 en CpuBecado.',
                    'details' => $becado
                ], 200);
            } else {
                return response()->json([
                    'message' => 'El estado ya estaba actualizado a 9 en CpuBecado.',
                    'details' => $becado
                ], 200);
            }
        }

        // Si no se encuentra en CpuBecado, buscar en CpuClientesTasty
        $clienteTasty = CpuClientesTasty::where('identificacion', $identificacion)->first();
        if ($clienteTasty) {
            if ($clienteTasty->id_estado !== 9) {
                $clienteTasty->id_estado = 9;
                $clienteTasty->save();
                return response()->json([
                    'message' => 'Estado actualizado correctamente a 9 en CpuClientesTasty.',
                    'details' => $clienteTasty
                ], 200);
            } else {
                return response()->json([
                    'message' => 'El estado ya estaba actualizado a 9 en CpuClientesTasty.',
                    'details' => $clienteTasty
                ], 200);
            }
        }

        // Si no se encuentra en ninguna tabla
        return response()->json([
            'message' => 'No se encontró registro con esa identificación en ninguna tabla.',
        ], 404);
    }

    public function getCargos()
    {
        // Obtener todos los cargos únicos
        $cargos = CpuClientesTasty::select('cargo_puesto')->distinct()->pluck('cargo_puesto')->sort();
        // Agregar "Todo Personal" como la primera opción
        $cargos->prepend('Todos');

        // Devolver los cargos como respuesta JSON
        return response()->json($cargos);
    }
}
