<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\CpuEvidencia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\CpuObjetivoNacional;
use App\Models\CpuElementoFundamental;
use App\Models\CpuFuenteInformacion;
use App\Models\CpuYear;
use App\Models\CpuSede;
use Illuminate\Support\Facades\Log;
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
            'id_elemento_fundamental' => 'required|integer',
            'descripcion' => 'required|string',
            'evidencia' => 'required|file|mimes:pdf|max:50000', // Max 50MB
            'year' => 'required|integer',
            'id_fuente_informacion' => 'required|integer',
            'id_sede' => 'required|integer|exists:cpu_sede,id',
        ]);

        $fuenteId = $request->input('id_elemento_fundamental');
        $descripcion = $request->input('descripcion');
        $evidenciaFile = $request->file('evidencia');
        $yearId = $request->input('year');
        $fuenteInformacionId = $request->input('id_fuente_informacion');
        $sedeId = $request->input('id_sede');

        // Verificar si la entrada 'year' es válida
        $year = CpuYear::find($yearId);
        if (!$year) {
            return response()->json(['error' => 'Año no encontrado'], 404);
        }

        // Verificar si la entrada 'id_elemento_fundamental' es válida
        $fuente = CpuElementoFundamental::find($fuenteId);
        if (!$fuente) {
            return response()->json(['error' => 'Elemento fundamental no encontrado'], 404);
        }

        // Verificar si la fuente de información es válida
        $fuenteInformacion = CpuFuenteInformacion::find($fuenteInformacionId);
        if (!$fuenteInformacion) {
            return response()->json(['error' => 'Fuente de información no encontrada'], 404);
        }

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

        $filePath = $year->descripcion . '/' . $fileName;

        $evidencia = new CpuEvidencia();
        $evidencia->id_elemento_fundamental = $fuenteId;
        $evidencia->descripcion = $descripcion;
        $evidencia->enlace_evidencia = $filePath;
        $evidencia->id_fuente_informacion = $fuenteInformacionId;
        $evidencia->id_sede = $sedeId;
        $evidencia->save();

        return response()->json(['message' => 'Evidencia agregada correctamente']);
    }

    public function actualizarEvidencia(Request $request, $id)
    {
        $request->validate([
            'id_elemento_fundamental' => 'required|integer',
            'descripcion' => 'required|string',
            'evidencia' => 'nullable|file|mimes:pdf|max:50000', // Opcional
            'year' => 'required|integer',
            'id_fuente_informacion' => 'required|integer',
        ]);

        $evidencia = CpuEvidencia::find($id);
        if (!$evidencia) {
            return response()->json(['error' => 'Evidencia no encontrada'], 404);
        }

        $evidencia->id_elemento_fundamental = $request->input('id_elemento_fundamental');
        $evidencia->descripcion = $request->input('descripcion');
        $evidencia->id_fuente_informacion = $request->input('id_fuente_informacion');

        // Verificar si hay un archivo nuevo
        if ($request->hasFile('evidencia')) {
            $year = CpuYear::find($request->input('year'));
            if (!$year) {
                return response()->json(['error' => 'Año no encontrado'], 404);
            }

            $fileName = $year->descripcion . '_' . str_replace(' ', '_', $request->input('descripcion')) . '.pdf';
            $yearFolder = public_path('Files/evidencias') . '/' . $year->descripcion;

            if (!file_exists($yearFolder)) {
                mkdir($yearFolder, 0777, true);
            }

            $fullPath = $yearFolder . '/' . $fileName;

            // Si el archivo ya existe, eliminarlo
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }

            // Mover el archivo al directorio de destino
            $request->file('evidencia')->move($yearFolder, $fileName);
            $filePath = $year->descripcion . '/' . $fileName;

            // Actualizar la referencia del archivo en la base de datos
            $evidencia->enlace_evidencia = $filePath;
        }

        $evidencia->save();

        return response()->json(['message' => 'Evidencia actualizada correctamente']);
    }


    public function eliminarEvidencia($id)
    {
        $evidencia = CpuEvidencia::find($id);
        if (!$evidencia) {
            return response()->json(['error' => 'Evidencia no encontrada'], 404);
        }

        // Eliminar el archivo asociado si existe
        if ($evidencia->enlace_evidencia && file_exists(public_path('Files/evidencias/' . $evidencia->enlace_evidencia))) {
            unlink(public_path('Files/evidencias/' . $evidencia->enlace_evidencia));
        }

        $evidencia->delete();

        return response()->json(['message' => 'Evidencia eliminada correctamente']);
    }



    public function consultarEvidencias()
    {
        // Obtener las evidencias ordenadas por id_fuente_informacion
        $evidencias = CpuEvidencia::orderBy('id_fuente_informacion', 'asc')->get();

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
        // Obtener los objetivos nacionales que pertenecen al año dado
        $objetivos = CpuObjetivoNacional::where('id_year', $ano)
            ->with([
                'estandares.elementosFundamentales.evidencias' => function ($query) {
                    $query->orderBy('id_fuente_informacion', 'asc');
                },
            ])
            ->get();

        // URL base de la aplicación
        $baseUrl = URL::to('/');

        // Preparar la respuesta
        $response = [];
        foreach ($objetivos as $objetivo) {
            $estandares = [];
            foreach ($objetivo->estandares as $estandar) {
                $elementosFundamentales = [];
                foreach ($estandar->elementosFundamentales as $elemento) {
                    // Obtener la sede basada en el campo id_sede
                    $sede = CpuSede::find($elemento->id_sede);

                    $evidencias = [];
                    foreach ($elemento->evidencias as $evidencia) {
                        // Consultar la fuente de información relacionada
                        $fuenteInformacion = CpuFuenteInformacion::find($evidencia->id_fuente_informacion);

                        // Construir la URL de la evidencia sin firmar
                        $urlEvidencia = $baseUrl . '/Files/evidencias/' . $evidencia->enlace_evidencia;

                        // Generar manualmente la firma
                        $expires = now()->addMinutes(30)->timestamp;
                        $signature = hash_hmac(
                            'sha256',
                            sprintf('/Files/evidencias/%s?expires=%s', $evidencia->enlace_evidencia, $expires),
                            config('app.key') // Clave de la aplicación
                        );

                        // Construir la URL firmada
                        $urlFirmada = sprintf(
                            '%s?expires=%s&signature=%s',
                            $urlEvidencia,
                            $expires,
                            $signature
                        );

                        // Asignar ambas URLs y la descripción de la fuente a la evidencia
                        $evidenciaData = [
                            'id' => $evidencia->id,
                            'descripcion' => $evidencia->descripcion,
                            'id_fuente_informacion' => $evidencia->id_fuente_informacion,
                            'fuente_informacion_descripcion' => $fuenteInformacion ? $fuenteInformacion->descripcion : null,
                            'enlace_evidencia' => [
                                'url_firmada' => $urlFirmada
                            ],
                            'sede' => [
                                'id' => $evidencia->id_sede,
                                'nombre' => CpuSede::where('id', $evidencia->id_sede)->value('nombre_sede'),
                            ],
                        ];
                        $evidencias[] = $evidenciaData;
                    }

                    $elementoFundamentalData = [
                        'id' => $elemento->id,
                        'descripcion' => $elemento->descripcion,
                        'sede' => [
                            'id' => $sede->id,
                            'nombre' => $sede->nombre_sede,
                        ],
                        'evidencias' => $evidencias
                    ];
                    $elementosFundamentales[] = $elementoFundamentalData;
                }
                $estandarData = [
                    'id' => $estandar->id,
                    'descripcion' => $estandar->descripcion,
                    'elementos_fundamentales' => $elementosFundamentales
                ];
                $estandares[] = $estandarData;
            }
            $objetivoData = [
                'id' => $objetivo->id,
                'descripcion' => $objetivo->descripcion,
                'estandares' => $estandares
            ];
            $response[] = $objetivoData;
        }

        return response()->json(['ano' => $ano, 'objetivos_nacionales' => $response]);
    }


    public function descargarArchivo($ano, $archivo)
    {
        // Lógica para descargar el archivo y devolverlo como respuesta
        $rutaArchivo = 'evidencias/' . $ano . '/' . $archivo;
        return response()->download(storage_path('app/' . $rutaArchivo));
    }
}
