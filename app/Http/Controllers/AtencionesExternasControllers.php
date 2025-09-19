<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Session;
use Carbon;

class AtencionesExternasControllers extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
        $this->auditoriaController = new AuditoriaControllers();
        $this->logController = new LogController();
    }

    public function getAtencionesExternas()
    {
        try {
            $data = DB::table('cpu_atenciones')
                ->select(
                    'id',
                    'id_funcionario',
                    'id_persona',
                    'via_atencion',
                    'motivo_atencion',
                    'fecha_hora_atencion',
                    'anio_atencion',
                    'created_at',
                    'updated_at',
                    'detalle_atencion',
                    'id_caso',
                    'id_tipo_usuario',
                    'evolucion_enfermedad',
                    'diagnostico',
                    'prescripcion',
                    'recomendacion',
                    'tipo_atencion',
                    'id_cie10',
                    'id_estado'
                )
                ->where('id_tipo_atencion', 'EXTERNA')
                ->get();

            // Retorna 200 siempre, incluso si no hay registros
            return response()->json($data, 200);
        } catch (\Exception $e) {
            $this->logController->saveLog(
                'Controlador: AtencionesExternasControllers, Función: getAtencionesExternas()',
                'Error al consultar Atenciones Externas: ' . $e->getMessage()
            );
            return response()->json(['message' => 'Error al consultar atenciones externas'], 500);
        }
    }

    public function guardarAtencionExterna(Request $request)
    {
        // $validator = Validator::make($request->all(), [
        //     'id_funcionario' => 'required|integer',
        //     'id_persona' => 'required|integer',
        //     'via_atencion' => 'required|string|max:100',
        //     'motivo_atencion' => 'required|string|max:500',
        //     'fecha_hora_atencion' => 'required|date',
        //     'anio_atencion' => 'required|integer',
        //     'detalle_atencion' => 'nullable|string',
        //     'id_caso' => 'nullable|integer',
        //     'id_tipo_usuario' => 'required|integer',
        //     'evolucion_enfermedad' => 'nullable|string',
        //     'diagnostico' => 'nullable|string|max:500',
        //     'prescripcion' => 'nullable|string|max:500',
        //     'recomendacion' => 'nullable|string|max:500',
        //     'tipo_atencion' => 'required|string|max:50',
        //     'id_cie10' => 'nullable|integer',
        //     'id_estado' => 'required|integer'
        // ]);

        // if ($validator->fails()) {
        //     return response()->json(['errors' => $validator->errors()], 422);
        // }

        try {
            $rutaArchivo = null;
            if ($request->hasFile('archivo_comprobante')) {
                $archivo = $request->file('archivo_comprobante');
                $nombreArchivo = time() . '_' . $archivo->getClientOriginalName();
                $rutaArchivo = $archivo->storeAs('evidencias_atenciones_externas', $nombreArchivo, 'public');


                
            }

            $idEncabezado = DB::table('cpu_atenciones')->insertGetId([
                'id_funcionario' => $request->id_funcionario,
                'id_persona' => $request->id_persona,
                'via_atencion' => 'PRESENCIAL',
                'motivo_atencion' => $request->descripcion_atencion,
                'fecha_hora_atencion' => now(),
                'anio_atencion' => now()->year,
                'detalle_atencion' => $request->descripcion_atencion,
                'tipo_atencion' => 'EXTERNA',
                'id_estado' => 1,
                'ruta_evidencia_externa' => $rutaArchivo, 
                'created_at' => now(),
                'updated_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Ingreso guardado correctamente',
                'id' => $idEncabezado
            ], 201);
        } catch (\Exception $e) {
            $this->logController->saveLog(
                'Controlador: AtencionesExternasControllers, Función: guardarAtencionExterna',
                'Error al guardar atención externa: ' . $e->getMessage()
            );

            return response()->json([
                'success' => false,
                'message' => 'Error al guardar atención externa',
                'error' => $e->getMessage()
            ], 500);
        }
    }



    public function index()
    {
        //
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
