<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class EgresosControllers extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
        $this->auditoriaController = new AuditoriaControllers();
        $this->logController = new LogController();
    }

    public function consultarEgresos()
    {
        try {
            $data = DB::table('cpu_encabezados_egresos as cee')
                ->select(
                    'cee.ee_id',

                    'cee.ee_id_funcionario',
                    'uf.name as nombre_funcionario',
                    'uf.email as email_funcionario',
                    'cee.ee_cedula_funcionario',

                    'cee.ee_id_paciente',
                    'p.nombres as nombre_paciente',
                    'p.cedula as cedula_paciente',
                    'p.celular as celular_paciente',

                    'cee.ee_detalle',
                    'cee.ee_id_estado',
                    'e.estado as nombre_estado',

                    'cee.ee_id_user',
                    'uu.name as nombre_usuario',
                    'uu.email as email_usuario',

                    'cee.ee_created_at',
                    'cee.ee_updated_at',
                    'cee.ee_observacion',
                    'cee.ee_id_atencion_medicina_general'
                )
                ->leftJoin('users as uf', 'cee.ee_id_funcionario', '=', 'uf.id') // funcionario
                ->leftJoin('users as uu', 'cee.ee_id_user', '=', 'uu.id')       // usuario que creó/modificó
                ->leftJoin('cpu_estados as e', 'cee.ee_id_estado', '=', 'e.id')
                ->leftJoin('cpu_personas as p', 'cee.ee_id_paciente', '=', 'p.id')
                ->get();

            return response()->json($data, 200);
        } catch (\Exception $e) {
            $this->logController->saveLog('Nombre de Controlador: EgresosControllers, Nombre de Funcion: consultarEgresos()', 'Error al consultar egresos: ' . $e->getMessage());
            Log::error('Error al consultar ingresos: ' . $e->getMessage());
            return response()->json(['error' => 'Error al consultar egresos: ' . $e->getMessage()], 500);
        }
    }

    public function getConsultarEgresosId($id)
    {
        try {
            $data = DB::table('cpu_encabezados_egresos')
                ->select(
                    'ee_id',
                    'ee_id_funcionario',
                    //'ee_cedula_funcionario',
                    'ee_id_paciente',
                    //'ee_cedula_paciente',
                    'ee_detalle',
                    'ee_id_estado',
                    'ee_id_user',
                    'ee_created_at',
                    'ee_updated_at',
                    'ee_observacion',
                    'ee_id_atencion_medicina_general'
                )
                ->where('ee_id', '=', $id)
                ->get();
            return response()->json($data, 200);
        } catch (\Exception $e) {
            $this->logController->saveLog('Nombre de Controlador: IngresosControllers, Nombre de Funcion:consultarIngresosId($id)', 'Error al consultar ingresos: ' . $e->getMessage());
            Log::error('Error al consultar ingresos: ' . $e->getMessage());
            return response()->json(['error' => 'Error al consultar ingresos: ' . $e->getMessage()], 500);
        }
    }

    public function guardarAtencionEgreso(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'idEgreso' => 'required|integer',
            //'observacion' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }
        $nroEgreso = DB::table('cpu_encabezados_egresos')
            ->where('ee_id', $request->idEgreso)
            ->value('ee_numero_egreso');

        try {
            $dataold = DB::table('cpu_encabezados_egresos')
                ->where('ee_id', $request->idEgreso)
                ->value('ee_observacion');

            $response = DB::table('cpu_encabezados_egresos')
                ->where('ee_id', $request->idEgreso)
                ->update(['ee_observacion' => $request->observacion]);

            $descripcionAuditoria = 'Se actualizó la obaservación de atención del egreso #: ' . $nroEgreso . ' con la abservación: ' . $request->observacion;
            $this->auditoriaController->auditar('cpu_encabezados_egresos', 'guardarAtencionEgreso(Request $request)', $dataold, $request->observacion, 'UPDATE', $descripcionAuditoria);


            $detalleEgreso = DB::table('cpu_encabezados_egresos')
                ->where('ee_id', $request->idEgreso)
                ->value('ee_detalle');

            if ($detalleEgreso) {
                // Convertir de JSON a array PHP
                $detalleEgreso = json_decode($detalleEgreso, true);

                // 3. REINSERTAR MOVIMIENTOS y RECALCULAR STOCK
                foreach ($detalleEgreso as $value) {
                    $idInsumo = $value['idInsumo'];
                    $cantidad = (int) $value['cantidad'];

                    // RECONSTRUIR STOCK ACTUAL antes de este egreso
                    $stockAnterior = DB::table('cpu_movimientos_inventarios')
                        ->where('mi_id_insumo', $idInsumo)
                        ->orderBy('mi_created_at', 'desc')
                        ->value('mi_stock_actual') ?? 0;

                    $stockNuevo = $stockAnterior - $cantidad;

                    DB::table('cpu_movimientos_inventarios')->insert([
                        'mi_id_insumo'       => $idInsumo,
                        'mi_cantidad'        => $cantidad,
                        'mi_stock_anterior'  => $stockAnterior,
                        'mi_stock_actual'    => $stockNuevo,
                        'mi_tipo_transaccion' => 2,
                        'mi_fecha'           => now(),
                        'mi_created_at'      => now(),
                        'mi_updated_at'      => now(),
                        'mi_user_id'         => $request->user()->id,
                        'mi_id_encabezado'   => $request->idEgreso,
                    ]);
                }
            }

            $descripcionAuditoria = 'Se actuaalizo los movimientos de inventario del egreso #: ' . $nroEgreso;
            $this->auditoriaController->auditar('cpu_movimientos_inventarios', 'guardarAtencionEgreso(Request $request)', '', $detalleEgreso, 'INSERT', $descripcionAuditoria);

            DB::table('cpu_encabezados_egresos')
                ->where('ee_id', $request->idEgreso)
                ->update(['ee_id_estado' => 2, 'ee_id_user' => $request->user()->id]);

            $descripcionAuditoria = 'Se actualizó el estado del egreso #: ' . $nroEgreso . ' a "Atendido"';
            $this->auditoriaController->auditar('cpu_encabezados_egresos', 'guardarAtencionEgreso(Request $request)', '', '', 'UPDATE', $descripcionAuditoria);

            // $descripcionAuditoria = 'Se actualizó la observación del egreso con ID: ' . $dataold . ' de : ' . $request->observacion . 'a' . $request->observacion;
            // $this->auditoriaController->auditar('cpu_encabezados_egresos', 'guardarObservacionEgreso(Request $request)',  $dataold,  $request->observacion, 'UPDATE', $descripcionAuditoria);

            return response()->json(['message' => 'Observación actualizada correctamente', "response" => $response], 200);
        } catch (\Exception $e) {
            $this->logController->saveLog('Nombre de Controlador: EgresosControllers, Nombre de Funcion: guardarObservacionEgreso()', 'Error al guardar observación: ' . $e->getMessage());
            Log::error('Error al guardar observación: ' . $e->getMessage());
            return response()->json(['error' => 'Error al guardar observación: ' . $e->getMessage()], 500);
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
