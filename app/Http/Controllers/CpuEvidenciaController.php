<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\CpuEvidencia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\CpuObjetivoNacional;
use App\Models\CpuFuenteInformacion;
use App\Models\CpuYear;
use Illuminate\Support\Facades\URL;


class CpuEvidenciaController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function agregarEvidencia(Request $request)
{
    $request->validate([
        'id_fuente_informacion' => 'required|integer',
        'descripcion' => 'required|string',
        'evidencia' => 'required|file|mimes:pdf|max:50000', // Max 50MB
    ]);

    $fuenteId = $request->input('id_fuente_informacion');
    $descripcion = $request->input('descripcion');
    $evidenciaFile = $request->file('evidencia');

    $year = CpuYear::find($fuenteId); // Assuming you have a CpuYear model
    $fuente = CpuFuenteInformacion::find($fuenteId); // Assuming you have a CpuFuenteInformacion model
    $fileName = $year->descripcion . '_' . str_replace(' ', '_', $descripcion) . '.pdf';

    // Verificar si la carpeta 'evidencias' existe en public/file
    $evidenciasFolder = public_path('Files/evidencias');
    if (!file_exists($evidenciasFolder)) {
        mkdir($evidenciasFolder, 0777, true); // Crear la carpeta 'evidencias'
    }

    // Verificar si la carpeta del año existe en public/file/evidencias
    $yearFolder = $evidenciasFolder . '/' . $year->descripcion;
    if (!file_exists($yearFolder)) {
        mkdir($yearFolder, 0777, true); // Crear la carpeta del año
    }

    // Mover el archivo a la carpeta del año
    $evidenciaFile->move($yearFolder, $fileName);

    $filePath = 'Files/evidencias/' . $year->descripcion . '/' . $fileName;

    $evidencia = new CpuEvidencia();
    $evidencia->id_fuente_informacion = $fuenteId;
    $evidencia->descripcion = $descripcion;
    $evidencia->enlace_evidencia = $filePath;
    $evidencia->save();

    return response()->json(['message' => 'Evidencia agregada correctamente']);
}


public function actualizarEvidencia(Request $request, $id)
{
    $request->validate([
        'descripcion' => 'required|string',
        'evidencia' => 'required|file|mimes:pdf|max:50000', // Max 50MB
    ]);

    $descripcion = $request->input('descripcion');
    $evidenciaFile = $request->file('evidencia');

    $evidencia = CpuEvidencia::findOrFail($id);

    $year = CpuYear::find($evidencia->id_fuente_informacion);

    // Construir la ruta del archivo existente en la carpeta publica
    $filePath = public_path('Files/evidencias/' . $year->descripcion . '/' . basename($evidencia->enlace_evidencia));

    if (file_exists($filePath)) {
        // Eliminar el archivo existente
        unlink($filePath);
    }

    // Verificar si la carpeta del año existe en public/Files/evidencias
    $yearFolder = public_path('Files/evidencias/' . $year->descripcion);
    if (!file_exists($yearFolder)) {
        mkdir($yearFolder, 0777, true); // Crear la carpeta del año
    }

    $fileName = str_replace(' ', '_', $descripcion) . '.pdf';
    $filePath = 'Files/evidencias/' . $year->descripcion . '/' . $fileName;
    $evidenciaFile->move($yearFolder, $fileName); // Mover el archivo a la carpeta del año

    // Actualizar la descripción y la ruta de la evidencia en la base de datos
    $evidencia->descripcion = $descripcion;
    $evidencia->enlace_evidencia = $filePath;
    $evidencia->save();

    return response()->json(['message' => 'Evidencia actualizada correctamente']);
}



public function eliminarEvidencia($id)
{
    $evidencia = CpuEvidencia::find($id);

    if (!$evidencia) {
        return response()->json(['warning' => true, 'message' => 'Evidencia no encontrada'], 404);
    }

    $year = CpuYear::find($evidencia->id_fuente_informacion);

    // Construir la ruta completa del archivo
    $filePath = public_path('Files/evidencias/' . $year->descripcion . '/' . $evidencia->enlace_evidencia);

    if (!file_exists($filePath)) {
        return response()->json(['warning' => true, 'message' => 'El archivo no existe'], 404);
    }

    try {
        // Eliminar el archivo asociado a la evidencia
        unlink($filePath);

        // Eliminar la evidencia de la base de datos
        $evidencia->delete();

        $fecha = now();

        DB::table('cpu_auditoria')->insert([
            'aud_user' => 'System',
            'aud_tabla' => 'cpu_evidencia',
            'aud_campo' => 'descripcion',
            'aud_dataold' => $evidencia->descripcion,
            'aud_datanew' => '',
            'aud_tipo' => 'ELIMINACIÓN',
            'aud_fecha' => $fecha,
            'aud_ip' => request()->ip(),
            'aud_tipoauditoria' => 3,
            'aud_descripcion' => "ELIMINACIÓN DE EVIDENCIA " . $evidencia->descripcion,
            'aud_nombreequipo' => gethostbyaddr(request()->ip()),
            'created_at' => $fecha,
            'updated_at' => $fecha,
        ]);

        return response()->json(['success' => true, 'message' => 'Evidencia eliminada correctamente']);
    } catch (\Throwable $th) {
        return response()->json(['warning' => true, 'message' => 'No se pudo eliminar la evidencia']);
    }
}


    public function consultarEvidencias()
    {
        $evidencias = CpuEvidencia::all();

        $baseUrl = URL::to('/'); // Obtiene la URL base de la aplicación

        // Agregar la URL base a la ruta de cada evidencia
        $evidencias = $evidencias->map(function ($evidencia) use ($baseUrl) {
            $evidencia->enlace_evidencia = $baseUrl . '/' . $evidencia->enlace_evidencia;
            return $evidencia;
        });

        return response()->json($evidencias);
    }

    public function obtenerInformacionPorAno($ano)
{
    $objetivos = CpuObjetivoNacional::whereHas('fuentesInformacion', function($query) use ($ano) {
        $query->where('id_year', $ano);
    })->with(['fuentesInformacion' => function($query) {
        $query->with('evidencias');
    }])->get();

    $baseUrl = URL::to('/'); // Obtiene la URL base de la aplicación

    // Agrupar datos por año, objetivo, sede, fuente de información y evidencia
    $objetivosPorAno = [];
    foreach ($objetivos as $objetivo) {
        $objetivoData = [
            'id' => $objetivo->id,
            'id_year' => $objetivo->id_year,
            'descripcion' => $objetivo->descripcion,
            'created_at' => $objetivo->created_at,
            'updated_at' => $objetivo->updated_at,
            'fuentes_informacion' => []
        ];
        foreach ($objetivo->fuentesInformacion as $fuente) {
            $fuenteData = [
                'sede' => $fuente->sede,
                'id' => $fuente->id,
                'id_objetivo' => $fuente->id_objetivo,
                'descripcion' => $fuente->descripcion,
                'created_at' => $fuente->created_at,
                'updated_at' => $fuente->updated_at,
                'evidencias' => $fuente->evidencias->map(function ($evidencia) use ($baseUrl, $ano) {
                    // Generar URL firmada para la evidencia
                    $urlFirmada = URL::temporarySignedRoute(
                        'descargar-archivo',
                        now()->addMinutes(30), // La URL expirará en 30 minutos
                        ['ano' => $ano, 'archivo' => $evidencia->enlace_evidencia]
                    );
                    // Construir la URL de la evidencia sin firmar
                    $urlEvidencia = $baseUrl . '/' . $evidencia->enlace_evidencia;
                    // Remover parte no deseada de la URL
                    $urlFirmada = str_replace('api/descargar-archivo/1/', '', $urlFirmada);
                    // Asignar ambas URLs a la evidencia
                    $evidencia->enlace_evidencia = [
                        'url_firmada' => $urlFirmada,
                        'url' => $urlEvidencia
                    ];
                    return $evidencia;
                })
            ];
            $objetivoData['fuentes_informacion'][] = $fuenteData;
        }
        $objetivosPorAno[] = $objetivoData;
    }

    return response()->json(['ano' => $ano, 'objetivos' => $objetivosPorAno]);
}


  

    public function descargarArchivo($ano, $archivo)
    {
        // Lógica para descargar el archivo y devolverlo como respuesta
        $rutaArchivo = 'evidencias/' . $ano . '/' . $archivo;
        return response()->download(storage_path('app/' . $rutaArchivo));
    }

    
    
}




