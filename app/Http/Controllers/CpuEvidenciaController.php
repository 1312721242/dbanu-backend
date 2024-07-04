<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\CpuEvidencia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\CpuObjetivoNacional;
use App\Models\CpuElementoFundamental;
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
        ]);

        $fuenteId = $request->input('id_elemento_fundamental');
        $descripcion = $request->input('descripcion');
        $evidenciaFile = $request->file('evidencia');

        $year = CpuYear::find($fuenteId); // Assuming you have a CpuYear model
        $fuente = CpuElementoFundamental::find($fuenteId); // Assuming you have a CpuElementoFundamental model
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
        $evidencia->save();

        return response()->json(['message' => 'Evidencia agregada correctamente']);
    }

    public function actualizarEvidencia(Request $request, $id)
    {
        $request->validate([
            'id_elemento_fundamental' => 'required|integer',
            'descripcion' => 'required|string',
            'evidencia' => 'file|mimes:pdf|max:50000', // Max 50MB
        ]);

        $evidencia = CpuEvidencia::find($id);

        if (!$evidencia) {
            return response()->json(['warning' => true, 'message' => 'Evidencia no encontrada'], 404);
        }

        $fuenteId = $request->input('id_elemento_fundamental');
        $descripcion = $request->input('descripcion');
        $evidenciaFile = $request->file('evidencia');

        $year = CpuYear::find($fuenteId);
        $fileName = $year->descripcion . '_' . str_replace(' ', '_', $descripcion) . '.pdf';
        $filePath = 'Files/evidencias/' . $year->descripcion . '/' . $fileName;

        if ($evidenciaFile) {
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

            // Actualizar el enlace de evidencia
            $evidencia->enlace_evidencia = $filePath;
        }

        $evidencia->id_elemento_fundamental = $fuenteId;
        $evidencia->descripcion = $descripcion;
        $evidencia->save();

        return response()->json(['message' => 'Evidencia actualizada correctamente']);
    }


    public function eliminarEvidencia($id)
    {
        $evidencia = CpuEvidencia::find($id);

        if (!$evidencia) {
            return response()->json(['warning' => true, 'message' => 'Evidencia no encontrada'], 404);
        }

        $year = CpuYear::find($evidencia->id_elemento_fundamental);

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
        // Obtener los objetivos nacionales que pertenecen al año dado
        $objetivos = CpuObjetivoNacional::where('id_year', $ano)
            ->with([
                'estandares.elementosFundamentales.evidencias',
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
                        // Generar URL firmada para la evidencia
                        $urlFirmada = URL::temporarySignedRoute(
                            'descargar-archivo',
                            now()->addMinutes(30), // La URL expirará en 30 minutos
                            ['ano' => $ano, 'archivo' => 'Files/evidencias/' . $evidencia->enlace_evidencia]
                        );

                        // Construir la URL de la evidencia sin firmar
                        $urlEvidencia = $baseUrl . '/' . $evidencia->enlace_evidencia;

                        // Remover parte no deseada de la URL
                        $urlFirmada = str_replace('api/descargar-archivo/1/', '', $urlFirmada);

                        // Asignar ambas URLs a la evidencia
                        $evidenciaData = [
                            'id' => $evidencia->id,
                            'descripcion' => $evidencia->descripcion,
                            'enlace_evidencia' => [
                                'url_firmada' => $urlFirmada
                            ]
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
