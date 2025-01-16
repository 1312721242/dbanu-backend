<?php

namespace App\Http\Controllers;

use App\Models\CpuAtencion;
use App\Models\CpuAtencionesTrabajoSocial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

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
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

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

        return response()->json(['message' => 'Atención de Trabajo Social guardada exitosamente', 'data' => $atencionTrabajoSocial]);
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

        // Verificar si hay un archivo nuevo
        if ($request->hasFile('url_informe')) {
            $nombre_informe = $request->file('url_informe')->getClientOriginalName();
            $segments = explode('_', $nombre_informe);
            $folderName = $segments[0]; // Obtener el primer segmento del nombre del archivo

            $folderPath = public_path('Files/informes_ts') . '/' . $folderName;

            if (!file_exists($folderPath)) {
                mkdir($folderPath, 0777, true);
            }

            $fullPath = $folderPath . '/' . $nombre_informe;

            // Si el archivo ya existe, eliminarlo
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }

            // Mover el archivo al directorio de destino
            $request->file('url_informe')->move($folderPath, $nombre_informe);
            $filePath = 'informes_ts/' . $folderName . '/' . $nombre_informe;

            // Iniciar una transacción
            DB::beginTransaction();

            try {
                // Actualizar la referencia del archivo en la base de datos
                $atencionTrabajoSocial = CpuAtencionesTrabajoSocial::findOrFail($request->input('id_atenciones'));
                $atencionTrabajoSocial->url_informe = $filePath;
                $atencionTrabajoSocial->save();

                // Confirmar la transacción
                DB::commit();

                return response()->json(['message' => 'Informe actualizado correctamente']);
            } catch (\Exception $e) {
                // Deshacer la transacción en caso de error
                DB::rollBack();
                Log::error('Error al actualizar el informe:', ['exception' => $e->getMessage()]);
                return response()->json(['error' => 'Error al actualizar el informe'], 500);
            }
        } else {
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
}
