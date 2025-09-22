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
            $data = DB::select(
                'SELECT 
                    e.ae_id,
                    e.ae_ruta_evidencia,
                    e.ae_created_at,
                    p.cedula AS cedula_paciente,
                    p.nombres AS nombre_paciente,
                    a.motivo_atencion,
                    a.id_estado,
                    a.detalle_atencion,
                    a.via_atencion,
                    a.fecha_hora_atencion,
                    a.tipo_atencion,
                    u.name AS nombre_funcionario,
                    u.email AS email_funcionario
                FROM cpu_atenciones_enfermeria e
                JOIN cpu_atenciones a ON e.ae_id_atencion = a.id
                JOIN cpu_personas p ON e.ae_id_paciente = p.id
                LEFT JOIN users u ON e.ae_id_user = u.id
                WHERE a.tipo_atencion = :tipo_atencion',
                ['tipo_atencion' => 'EXTERNA']
            );
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
                $archivo->move(public_path('Files/evidencias_atenciones_externas'), $nombreArchivo);
                $rutaArchivo = 'Files/evidencias_atenciones_externas/' . $nombreArchivo;
            }

            $idAtencion = DB::table('cpu_atenciones')->insertGetId([
                'id_funcionario'       => $request->id_funcionario,
                'id_persona'           => $request->id_persona,
                'via_atencion'         => 'PRESENCIAL',
                'motivo_atencion'      => $request->descripcion_atencion,
                'fecha_hora_atencion'  => now(),
                'anio_atencion'        => now()->year,
                'detalle_atencion'     => $request->descripcion_atencion,
                'tipo_atencion'        => 'EXTERNA',
                'id_estado'            => 1,
                'created_at'           => now(),
                'updated_at'           => now(),
            ]);


            DB::table('cpu_atenciones_enfermeria')->insert([
                'ae_id_paciente'    => $request->id_persona,
                'ae_id_atencion'    => $idAtencion,
                'ae_tipo_servicio'  => $request->tipo_servicio,
                'ae_ruta_evidencia' => $rutaArchivo,
                'ae_id_user'        => $request->id_funcionario,
                'ae_created_at'     => now(),
                'ae_updated_at'     => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Ingreso guardado correctamente',
                'id' => $idAtencion
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
