<?php

namespace App\Http\Controllers;

use App\Models\CpuAtencion;
use App\Models\CpuAtencionesTrabajoSocial;
use App\Models\CpuDerivacion;
use App\Models\CpuTurno;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
class CpuAtencionesTrabajoSocialController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Convertir fecha_hora_atencion al formato requerido
        $fecha_hora_atencion = \Carbon\Carbon::createFromFormat('Y-m-d\TH:i', $request->input('fecha_hora_atencion'))->format('Y-m-d H:i:s');
        $request->merge(['fecha_hora_atencion' => $fecha_hora_atencion]);

        // Validar los datos para guardar la atención
        $validator = Validator::make($request->all(), [
            'id_funcionario' => 'required|integer',
            'id_persona' => 'required|integer',
            'via_atencion' => 'required|string',
            'motivo_atencion' => 'required|string',
            'fecha_hora_atencion' => 'required|date_format:Y-m-d H:i:s',
            'anio_atencion' => 'required|integer',
            'tipo_usuario' => 'required|integer',
            'tipo_atencion' => 'required|string',
            'detalle_atencion' => 'required|string',
            'tipo_modal' => 'required|string',
            'id_derivacion' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        // Iniciar una transacción
        DB::beginTransaction();

        try {
            // Guardar la atención
            $atencion = new CpuAtencion();
            $atencion->id_funcionario = $request->input('id_funcionario');
            $atencion->id_persona = $request->input('id_persona');
            $atencion->via_atencion = $request->input('via_atencion');
            $atencion->motivo_atencion = $request->input('motivo_atencion');
            $atencion->detalle_atencion = $request->input('detalle_atencion');
            $atencion->fecha_hora_atencion = $request->input('fecha_hora_atencion');
            $atencion->anio_atencion = $request->input('anio_atencion');
            $atencion->id_tipo_usuario = $request->input('tipo_usuario');
            $atencion->tipo_atencion = $request->input('tipo_atencion');
            $atencion->save();

            // Validar los datos para guardar la atención de trabajo social
            $validated = $request->validate([
                'tipo_informe' => 'required|string',
                'requiriente' => 'required|string',
                'numero_tramite' => 'required|string',
                'detalle_general' => 'required|string',
                'observaciones' => 'required|string',
                'periodo' => 'required|string',
            ]);

            // Agregar el id_atenciones al array validado
            $validated['id_atenciones'] = $atencion->id;

            // Guardar la atención de trabajo social
            $atencionTrabajoSocial = CpuAtencionesTrabajoSocial::create($validated);

            if ($request->input('tipo_modal') === 'derivacion') {
                // Actualizar la derivación
                $derivacion = CpuDerivacion::findOrFail($request->input('id_derivacion'));
                $derivacion->id_estado_derivacion = 2;
                $derivacion->save();

                // Actualizar el estado del turno relacionado
                $turno = CpuTurno::findOrFail($derivacion->id_turno_asignado);
                $turno->estado = 2;
                $turno->save();
            }

            // Auditoría
            $this->auditar('cpu_atenciones_trabajo_social', 'id', '', $atencionTrabajoSocial->id, 'INSERCION', "INSERCION DE NUEVA ATENCION DE TRABAJO SOCIAL: {$atencionTrabajoSocial->id},
                                                                                FUNCIONARIO: {$request->input('id_funcionario')},
                                                                                PACIENTE: {$request->input('id_persona')},
                                                                                VIA DE ATENCION: {$request->input('via_atencion')},
                                                                                MOTIVO DE ATENCION: {$request->input('motivo_atencion')},
                                                                                FECHA Y HORA DE ATENCION: {$request->input('fecha_hora_atencion')}", $request);

            // Confirmar la transacción
            DB::commit();

            return response()->json(['message' => 'Atención de Trabajo Social guardada exitosamente', 'data' => $atencionTrabajoSocial]);
        } catch (\Exception $e) {
            // Deshacer la transacción en caso de error
            DB::rollBack();
            Log::error('Error al guardar la atención de trabajo social:', ['exception' => $e->getMessage()]);
            return response()->json(['error' => 'Error al guardar la atención de trabajo social'], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(CpuAtencionesTrabajoSocial $cpuAtencionesTrabajoSocial)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CpuAtencionesTrabajoSocial $cpuAtencionesTrabajoSocial)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        $request->validate([
            'url_informe' => 'required|file|mimes:pdf|max:50000', // Cambiado a required
            'id_atenciones' => 'required|integer',
            'nombre_informe' => 'required|string',
        ]);

        if ($request->hasFile('url_informe')) {
            Log::info('Archivo recibido correctamente.');
            $nombre_informe = $request->input('nombre_informe'); // Usar nombre_informe del request
            $id_atenciones = $request->input('id_atenciones');

            // Obtener el segmento del nombre del archivo
            $nombre_carpeta = 'informes_ts';
            $nombre_archivo = pathinfo($nombre_informe, PATHINFO_FILENAME) . '_' . $id_atenciones; // Extraer solo el nombre base sin extensión

            // Verificar si existe un registro con url_informe en la base de datos
            $atencionTrabajoSocial = DB::table('cpu_atenciones_trabajo_social')->where('id_atenciones', $request->input('id_atenciones'))->first();
            if (!$atencionTrabajoSocial) {
                return response()->json(['error' => 'No se encontró el registro con el id especificado.'], 404);
            }

            // Eliminar el archivo existente si aplica
            if (!empty($atencionTrabajoSocial->url_informe)) {
                $rutaArchivoExistente = public_path('Files/' . $atencionTrabajoSocial->url_informe);

                if (file_exists($rutaArchivoExistente)) {
                    Log::info('Archivo existente encontrado. Eliminándolo: ' . $rutaArchivoExistente);
                    unlink($rutaArchivoExistente);
                }
            }

            // Crear la ruta completa del directorio dinámico
            $folderPath = public_path('Files/' . $nombre_carpeta);

            // Verificar si el directorio existe, de lo contrario crearlo
            if (!file_exists($folderPath)) {
                Log::info('Directorio no existe. Creándolo: ' . $folderPath);
                if (!mkdir($folderPath, 0777, true) && !is_dir($folderPath)) {
                    return response()->json(['error' => 'Error al crear el directorio para el archivo.'], 500);
                }
            }

            // Ruta completa del archivo nuevo
            $nombre_archivo_final = $nombre_archivo . '.pdf';
            $fullPath = $folderPath . '/' . $nombre_archivo_final;

            // Mover el archivo subido al directorio correspondiente
            $request->file('url_informe')->move($folderPath, $nombre_archivo_final);
            Log::info('Archivo movido a: ' . $fullPath);

            // Guardar la nueva ruta relativa en la base de datos
            $filePath = $nombre_carpeta . '/' . $nombre_archivo_final;

            DB::beginTransaction();

            try {
                DB::table('cpu_atenciones_trabajo_social')->where('id_atenciones', $request->input('id_atenciones'))->update([
                    'url_informe' => $filePath
                ]);

                // Auditoría
                $this->auditar('cpu_atenciones_trabajo_social', 'id', '', $atencionTrabajoSocial->id, 'MODIFICACION', "ACTUALIZACION DE INFORME DE ATENCION DE TRABAJO SOCIAL: {$atencionTrabajoSocial->id},
                                                                                    INFORME: {$request->input('nombre_informe')}", $request);

                DB::commit();

                return response()->json(['message' => 'Informe actualizado correctamente', 'url_informe' => $filePath], 200);
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Error al actualizar el informe:', ['exception' => $e->getMessage()]);
                return response()->json(['error' => 'Error al actualizar el informe'], 500);
            }
        } else {
            Log::info('No se recibió un archivo para procesar.');
            return response()->json(['message' => 'No se ha proporcionado ningún archivo para actualizar'], 400);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CpuAtencionesTrabajoSocial $cpuAtencionesTrabajoSocial)
    {
        //
    }

    // Función para auditar
    private function auditar($tabla, $campo, $dataOld, $dataNew, $tipo, $descripcion, $request = null)
    {
        $usuario = $request ? $request->user()->name : auth()->user()->name;
        $ip = $request ? $request->ip() : request()->ip();
        $ipv4 = gethostbyname(gethostname());
        $publicIp = file_get_contents('http://ipecho.net/plain');
        $ioConcatenadas = 'IP LOCAL: ' . $ip . '  --IPV4: ' . $ipv4 . '  --IP PUBLICA: ' . $publicIp;
        $nombreequipo = gethostbyaddr($ip);
        $userAgent = $request ? $request->header('User-Agent') : request()->header('User-Agent');
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
        $codigo_auditoria = strtoupper($tabla . '_' . $campo . '_' . $tipo );
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
