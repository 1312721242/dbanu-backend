<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

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
                    'cee.ee_numero_egreso',

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
                ->leftJoin('users as uf', 'cee.ee_id_funcionario', '=', 'uf.id')
                ->leftJoin('users as uu', 'cee.ee_id_user', '=', 'uu.id')
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

    // public function guardarAtencionEgreso(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'idEgreso' => 'required|integer',
    //         //'observacion' => 'required|string|max:255',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json(['error' => $validator->errors()], 422);
    //     }
    //     $nroEgreso = DB::table('cpu_encabezados_egresos')
    //         ->where('ee_id', $request->idEgreso)
    //         ->value('ee_numero_egreso');

    //     try {
    //         // $dataold = DB::table('cpu_encabezados_egresos')
    //         //     ->where('ee_id', $request->idEgreso)
    //         //     ->value('ee_observacion');

    //         // $response = DB::table('cpu_encabezados_egresos')
    //         //     ->where('ee_id', $request->idEgreso)
    //         //     ->update(['ee_observacion' => $request->observacion]);

    //         // $descripcionAuditoria = 'Se actualizó la obaservación de atención del egreso #: ' . $nroEgreso . ' con la abservación: ' . $request->observacion;
    //         // $this->auditoriaController->auditar('cpu_encabezados_egresos', 'guardarAtencionEgreso(Request $request)', $dataold, $request->observacion, 'UPDATE', $descripcionAuditoria);


    //         $detalleEgreso = DB::table('cpu_encabezados_egresos')
    //             ->where('ee_id', $request->idEgreso)
    //             ->value('ee_detalle');

    //         if ($detalleEgreso) {
    //             // Convertir de JSON a array PHP
    //             $detalleEgreso = json_decode($detalleEgreso, true);

    //             // 3. REINSERTAR MOVIMIENTOS y RECALCULAR STOCK
    //             foreach ($detalleEgreso as $value) {
    //                 $idInsumo = $value['idInsumo'];
    //                 $cantidad = (int) $value['cantidad'];
    //                 // RECONSTRUIR STOCK ACTUAL antes de este egreso
    //                 $stockAnterior = DB::table('cpu_movimientos_inventarios')
    //                     ->where('mi_id_insumo', $idInsumo)
    //                     ->orderBy('mi_created_at', 'desc')
    //                     ->value('mi_stock_actual') ?? 0;

    //                 // Validar si hay suficiente stock
    //                 if ($cantidad > $stockAnterior) {
    //                     return response()->json([
    //                         'success' => false,
    //                         'message' => "Stock insuficiente. Solo tienes {$stockAnterior} disponibles.",
    //                         'stock_disponible' => $stockAnterior
    //                     ], 400);
    //                 }

    //                 $stockNuevo = $stockAnterior - $cantidad;

    //                 DB::table('cpu_movimientos_inventarios')->insert([
    //                     'mi_id_insumo'       => $idInsumo,
    //                     'mi_cantidad'        => $cantidad,
    //                     'mi_stock_anterior'  => $stockAnterior,
    //                     'mi_stock_actual'    => $stockNuevo,
    //                     'mi_tipo_transaccion' => 2,
    //                     'mi_fecha'           => now(),
    //                     'mi_created_at'      => now(),
    //                     'mi_updated_at'      => now(),
    //                     'mi_user_id'         => $request->user()->id,
    //                     'mi_id_encabezado'   => $request->idEgreso,
    //                 ]);
    //             }
    //         }

    //         $descripcionAuditoria = 'Se actuaalizo los movimientos de inventario del egreso #: ' . $nroEgreso;
    //         $this->auditoriaController->auditar('cpu_movimientos_inventarios', 'guardarAtencionEgreso(Request $request)', '', $detalleEgreso, 'INSERT', $descripcionAuditoria);

    //         DB::table('cpu_encabezados_egresos')
    //             ->where('ee_id', $request->idEgreso)
    //             ->update(['ee_id_estado' => 2, 'ee_id_user' => $request->user()->id]);

    //         $descripcionAuditoria = 'Se actualizó el estado del egreso #: ' . $nroEgreso . ' a "Atendido"';
    //         $this->auditoriaController->auditar('cpu_encabezados_egresos', 'guardarAtencionEgreso(Request $request)', '', '', 'UPDATE', $descripcionAuditoria);

    //         // $descripcionAuditoria = 'Se actualizó la observación del egreso con ID: ' . $dataold . ' de : ' . $request->observacion . 'a' . $request->observacion;
    //         // $this->auditoriaController->auditar('cpu_encabezados_egresos', 'guardarObservacionEgreso(Request $request)',  $dataold,  $request->observacion, 'UPDATE', $descripcionAuditoria);

    //         return response()->json(['message' => 'Observación actualizada correctamente', "response" => $response], 200);
    //     } catch (\Exception $e) {
    //         $this->logController->saveLog('Nombre de Controlador: EgresosControllers, Nombre de Funcion: guardarObservacionEgreso()', 'Error al guardar observación: ' . $e->getMessage());
    //         Log::error('Error al guardar observación: ' . $e->getMessage());
    //         return response()->json(['error' => 'Error al guardar observación: ' . $e->getMessage()], 500);
    //     }
    // }


    public function guardarAtencionEgreso2(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'idEgreso' => 'required|integer',
        ]);

        if ($validator->fails()) {
            $mensaje = json_encode($validator->errors());
            $this->logController->saveLog(
                'Nombre de Controlador: EgresosController.php, Nombre de la función: guardarAtencionEgreso(Request $request)',
                'Validación fallida: ' . $mensaje
            );

            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $nroEgreso = DB::table('cpu_encabezados_egresos')
            ->where('ee_id', $request->idEgreso)
            ->value('ee_numero_egreso');

        try {
            DB::beginTransaction();
            $estado = DB::select(
                "SELECT ee_id_estado FROM cpu_encabezados_egresos WHERE ee_id = :idEgreso",
                ['idEgreso' => $request->idEgreso]
            );

            if (!empty($estado) && $estado[0]->ee_id_estado == 2) {
                $this->logController->saveLog(
                    'Nombre de controlador: EgresosController, Nombre de la función: guardarAtencionEgreso(Request $request)',
                    "El egreso #{$nroEgreso} ya ha sido atendido previamente."
                );

                return response()->json([
                    'success' => false,
                    'message' => "El egreso ya ha sido atendido previamente.",
                ], 400);
            }

            $detalleEgreso = DB::table('cpu_encabezados_egresos')
                ->where('ee_id', $request->idEgreso)
                ->value('ee_detalle');

            if ($detalleEgreso) {
                $detalleEgreso = json_decode($detalleEgreso, true);

                foreach ($detalleEgreso as $value) {
                    $idInsumo = $value['idInsumo'];
                    $cantidad = (int) $value['cantidad'];

                    $stockAnterior = DB::table('cpu_movimientos_inventarios')
                        ->where('mi_id_insumo', $idInsumo)
                        ->orderBy('mi_created_at', 'desc')
                        ->value('mi_stock_actual') ?? 0;

                    if ($cantidad > $stockAnterior) {
                        $mensajeStock = "Stock insuficiente para el insumo ID {$idInsumo}. Solo tienes {$stockAnterior} disponibles.";
                        $this->logController->saveLog(
                            'Nombre de Controlador: EgresosController, Nombre de la función: guardarAtencionEgreso(Request $request)',
                            $mensajeStock
                        );

                        DB::rollBack();
                        return response()->json([
                            'success' => false,
                            'message' => $mensajeStock,
                            'stock_disponible' => $stockAnterior,
                            'idInsumo' => $idInsumo
                        ], 400);
                    }

                    $stockNuevo = $stockAnterior - $cantidad;
                    $insertId = DB::table('cpu_movimientos_inventarios')->insertGetId([
                        'mi_id_insumo'        => $idInsumo,
                        'mi_cantidad'         => $cantidad,
                        'mi_stock_anterior'   => $stockAnterior,
                        'mi_stock_actual'     => $stockNuevo,
                        'mi_tipo_transaccion' => 2,
                        'mi_fecha'            => Carbon::now()->toDateTimeString(),
                        'mi_created_at'       => Carbon::now()->toDateTimeString(),
                        'mi_updated_at'       => Carbon::now()->toDateTimeString(),
                        'mi_user_id'          => $request->user()->id,
                        'mi_id_encabezado'    => $request->idEgreso,
                    ]);

                    // Auditoría individual de insert
                    $descripcionAuditoria = "Se registró movimiento de egreso ID {$insertId} para insumo ID {$idInsumo}";
                    $this->auditoriaController->auditar(
                        'cpu_movimientos_inventarios',
                        'guardarAtencionEgreso(Request $request)',
                        '',
                        json_encode($request->all()),
                        'INSERT',
                        $descripcionAuditoria
                    );

                    $this->logController->saveLog(
                        'Nombre de controlador: EgresosController, Nombre de la función: guardarAtencionEgreso(Request $request)',
                        $descripcionAuditoria
                    );
                }
            }

            // Actualizar estado del egreso
            $update = DB::table('cpu_encabezados_egresos')
                ->where('ee_id', $request->idEgreso)
                ->update([
                    'ee_id_estado' => 2,
                    'ee_id_user' => $request->user()->id
                ]);

            // Auditoría del update
            $descripcionAuditoria = "Se actualizó el estado del egreso #{$nroEgreso} a atendido (2)";
            $this->auditoriaController->auditar(
                'cpu_encabezados_egresos',
                'guardarAtencionEgreso(request $request)',
                '',
                json_encode($update),
                'UPDATE',
                $descripcionAuditoria
            );

            $this->logController->saveLog(
                'Nombre de controlador: EgresosController, Nombre de la función: guardarAtencionEgreso(request $request)',
                "Atención guardada correctamente para egreso #{$nroEgreso}"
            );

            // Auditoría general final
            $descripcionAuditoriaGeneral = "Se registró la atención del egreso #{$nroEgreso} con todos los movimientos de insumos y actualización de estado.";
            $this->auditoriaController->auditar(
                'cpu_encabezados_egresos',
                'guardarAtencionEgreso()',
                '',
                json_encode($request->all()),
                'INSERT',
                $descripcionAuditoriaGeneral
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Atención guardada correctamente."
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al guardar atención: ' . $e->getMessage());
            $this->logController->saveLog(
                'Nombre de controlador: EgresosControllers, Nombre de la función: guardarAtencionEgreso(request $request)',
                'Error al guardar la atención: ' . $e->getMessage()
            );

            return response()->json([
                'success' => false,
                'message' => 'Error al guardar la atención: ' . $e->getMessage()
            ], 500);
        }
    }

    public function guardarAtencionEgreso(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'idEgreso' => 'required|integer',
            'estado'   => 'required|integer'
        ]);

        if ($validator->fails()) {
            $mensaje = json_encode($validator->errors());
            $this->logController->saveLog(
                'EgresosController -> guardarAtencionEgreso',
                'Validación fallida: ' . $mensaje
            );

            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $nroEgreso = DB::table('cpu_encabezados_egresos')
            ->where('ee_id', $request->idEgreso)
            ->value('ee_numero_egreso');

        $estadoEgreso = $request->estado;
        $auditoriaConsolidada = [];

        try {
            DB::beginTransaction();

            $detalleEgreso = DB::table('cpu_encabezados_egresos')
                ->where('ee_id', $request->idEgreso)
                ->value('ee_detalle');

            // Si es estado 5, se suman stocks y registran movimientos
            if ($estadoEgreso == 5 && $detalleEgreso) {
                $detalleEgreso = json_decode($detalleEgreso, true);

                foreach ($detalleEgreso as $value) {
                    $idInsumo = $value['idInsumo'];
                    $cantidad = (int) $value['cantidad'];

                    $stock = DB::select("
                        SELECT mi_stock_actual
                        FROM cpu_movimientos_inventarios
                        WHERE mi_id = (
                            SELECT MAX(mi_id)
                            FROM cpu_movimientos_inventarios
                            WHERE mi_id_insumo = :idInsumo
                        )
                    ", ['idInsumo' => $value['idInsumo']]);

                    $stockAnterior = $stock[0]->mi_stock_actual ?? 0;

                    $stockNuevo = $stockAnterior - $cantidad;

                    $insertId = DB::table('cpu_movimientos_inventarios')->insertGetId([
                        'mi_id_insumo'        => $idInsumo,
                        'mi_cantidad'         => $cantidad,
                        'mi_stock_anterior'   => $stockAnterior,
                        'mi_stock_actual'     => $stockNuevo,
                        'mi_tipo_transaccion' => 2,
                        'mi_fecha'            => Carbon::now()->toDateTimeString(),
                        'mi_created_at'       => Carbon::now()->toDateTimeString(),
                        'mi_updated_at'       => Carbon::now()->toDateTimeString(),
                        'mi_user_id'          => $request->user()->id,
                        'mi_id_encabezado'    => $request->idEgreso,
                    ], 'mi_id');

                    $auditoriaConsolidada[] = [
                        'accion'        => 'MOVIMIENTO_INSUMO',
                        'descripcion'   => "Movimiento registrado: ID {$insertId}, insumo {$idInsumo}, stock anterior {$stockAnterior}, stock nuevo {$stockNuevo}",
                        'idMovimiento'  => $insertId,
                        'insumo'        => $idInsumo,
                        'stockAnterior' => $stockAnterior,
                        'stockNuevo'    => $stockNuevo
                    ];
                }
            }

            $update = DB::table('cpu_encabezados_egresos')
                ->where('ee_id', $request->idEgreso)
                ->update([
                    'ee_id_estado' => $estadoEgreso == 5 ? 5 : 2,
                    'ee_observacion' => $request->observacion ?? null,
                    'ee_id_user'   => $request->user()->id
                ]);

            $auditoriaConsolidada[] = [
                'accion'      => 'UPDATE_ESTADO',
                'descripcion' => "Se actualizó el estado del egreso #{$nroEgreso} a {$estadoEgreso}",
                'nuevoEstado' => $estadoEgreso
            ];

            // Auditoría consolidada final
            $this->auditoriaController->auditar(
                'cpu_encabezados_egresos',
                'guardarAtencionEgreso(Request $request) - AUDITORIA CONSOLIDADA',
                '',
                json_encode($request->all()),
                'INSERT',
                json_encode($auditoriaConsolidada),
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Atención guardada correctamente."
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            $this->logController->saveLog(
                'EgresosController -> guardarAtencionEgreso',
                'Error al guardar la atención: ' . $e->getMessage()
            );

            return response()->json([
                'success' => false,
                'message' => 'Error al guardar la atención: ' . $e->getMessage()
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
