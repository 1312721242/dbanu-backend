<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as ReaderXlsx;
use App\Models\CpuLegalizacionMatricula;
use Symfony\Component\HttpFoundation\Response;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use App\Models\CpuCasosMatricula;
use Illuminate\Support\Facades\DB;
use App\Models\CpuSecretariaMatricula;
use App\Models\CpuSede;

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
        'tipo_matricula'
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
                'tipo_matricula' => $row['Z']?? null,
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

//cuenta casos matricula
public function consultarNumCasos()
{
    // Consulta para obtener el número de casos pendientes agrupados por sede y secretaria
    $casosPorSecretaria = CpuCasosMatricula::select('id_secretaria', 'id_estado', DB::raw('count(*) as total'))
        ->whereIn('id_estado', [13, 15])
        ->groupBy('id_secretaria', 'id_estado')
        ->get();

    // Obtener la información de la sede y nombre de cada secretaria
    $secretarias = CpuSecretariaMatricula::all()->keyBy('id');
    $sedes = CpuSede::all()->keyBy('id'); // Obtener todas las sedes

    // Organizar la información en el formato deseado
    $result = [];
    foreach ($casosPorSecretaria as $caso) {
        $sedeId = $secretarias[$caso->id_secretaria]->id_sede;
        $sedeNombre = $sedes[$sedeId]->nombre_sede ?? 'Sede Desconocida'; // Obtener el nombre de la sede

        // Verificar si ya existe una entrada para esta sede en el resultado
        if (!isset($result[$sedeNombre])) {
            $result[$sedeNombre] = [];
        }

        $result[$sedeNombre][] = [
            'id_secretaria' => $caso->id_secretaria,
            'Nombresecretaria' => $secretarias[$caso->id_secretaria]->nombre,
            'casos_nuevos' => ($caso->id_estado == 13) ? $caso->total : 0,
            'casos_corrección' => ($caso->id_estado == 15) ? $caso->total : 0,
        ];
    }

    return response()->json($result);
}


//funcion para reasignar casos

public function reasignarCasos(Request $request)
{
    $request->validate([
        'id_sede' => 'required|integer',
        'id_periodo' => 'required|integer',
    ]);

    // Consultar el número total de casos con estado 13
    $numCasosEstado13 = CpuCasosMatricula::where('id_estado', 13)->count();

    // Consultar el número de secretarias habilitadas en la sede
    $numSecretariasHabilitadas = CpuSecretariaMatricula::where('id_sede', $request->id_sede)
        ->where('habilitada', true)
        ->count();

    // Verificar que hay al menos una secretaria habilitada para reasignar casos
    if ($numSecretariasHabilitadas == 0) {
        return response()->json(['message' => 'No hay secretarias habilitadas para reasignar casos'], 400);
    }

    // Calcular el número de casos a reasignar por secretaria
    $casosPorSecretaria = $numCasosEstado13 / $numSecretariasHabilitadas;

    // Obtener las secretarias habilitadas de la sede
    $secretariasHabilitadas = CpuSecretariaMatricula::where('id_sede', $request->id_sede)
        ->where('habilitada', true)
        ->pluck('id');

    // Reasignar casos a las secretarias
    $casos = CpuCasosMatricula::where('id_estado', 13)
        ->whereIn('id_secretaria', $secretariasHabilitadas)
        ->orderBy('fecha_creacion', 'ASC')
        ->limit($casosPorSecretaria)
        ->get();

    foreach ($casos as $caso) {
        $caso->id_secretaria = $request->id_secretaria;
        $caso->save();
    }

    return response()->json(['message' => 'Casos reasignados correctamente']);
}
//actualizar el email
public function actualizarEmail(Request $request, $id)
{
    $request->validate([
        'email' => 'required|email|unique:cpu_legalizacion_matricula,email'
    ]);

    $email = $request->input('email');
    $usuario = $request->user()->name;
    $ip = $request->ip();
    $nombreequipo = gethostbyaddr($ip);
    $fecha = now();

    try {
        DB::table('cpu_legalizacion_matricula')->where('id', $id)->update([
            'email' => $email,
            'updated_at' => $fecha,
        ]);

        DB::table('cpu_auditoria')->insert([
            'aud_user' => $usuario,
            'aud_tabla' => 'cpu_legalizacion_matricula',
            'aud_campo' => 'email',
            'aud_dataold' => '',
            'aud_datanew' => $email,
            'aud_tipo' => 'MODIFICACIÓN',
            'aud_fecha' => $fecha,
            'aud_ip' => $ip,
            'aud_tipoauditoria' => 2,
            'aud_descripcion' => "MODIFICACIÓN DE EMAIL $email",
            'aud_nombreequipo' => $nombreequipo,
            'created_at' => $fecha,
            'updated_at' => $fecha,
        ]);

        return response()->json(['success' => true, 'message' => 'Email actualizado correctamente']);
    } catch (\Throwable $th) {
        return response()->json(['warning' => true, 'message' => 'No se pudo actualizar el email, ya existe un email']);
    }
}



}
