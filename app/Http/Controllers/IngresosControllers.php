<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class IngresosControllers extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
        $this->auditoriaController = new AuditoriaControllers();
        $this->logController = new LogController();
    }

    public function consultarIngresos()
    {
        try {
            $data = DB::table('cpu_encabezados_ingresos as ei')
                ->leftJoin('cpu_proveedores as p', 'ei.ei_id_proveedor', '=', 'p.prov_id')
                ->leftJoin('cpu_estados as e', 'ei.ei_id_estado', '=', 'e.id')
                ->select(
                    'ei.ei_id',
                    'ei.ei_numero_comprobante',
                    'ei.ei_tipo_adquisicion',
                    'ei.ei_id_funcionario',
                    'ei.ei_id_proveedor',
                    'ei.ei_fecha_emision',
                    'ei.ei_fecha_vencimiento',
                    'ei.ei_created_at',
                    'ei.ei_updated_at',
                    'ei.ei_id_user',
                    'ei.ei_detalle_producto',
                    'ei.ei_id_estado',
                    'ei.ei_numero_ingreso',
                    'ei.ei_ruta_comprobante',
                    'p.prov_nombre as proveedor_nombre',
                    'p.prov_ruc',
                    'e.estado as estado_nombre'
                )
                ->where('ei.ei_id_estado', '=', 8)
                ->get();
            return response()->json($data, 200);
        } catch (\Exception $e) {
            $this->logController->saveLog('Nombre de Controlador: IngresosControllers, Nombre de Funcion: consultarIngresos()', 'Error al consultar ingresos: ' . $e->getMessage());
            Log::error('Error al consultar ingresos: ' . $e->getMessage());
            return response()->json(['error' => 'Error al consultar ingresos: ' . $e->getMessage()], 500);
        }
    }

    public function getConsultarIngresosId($id)
    {
        try {
            $data = DB::table('cpu_encabezados_ingresos as ei')
                ->leftJoin('cpu_proveedores as p', 'ei.ei_id_proveedor', '=', 'p.prov_id')
                ->leftJoin('cpu_estados as e', 'ei.ei_id_estado', '=', 'e.id')
                ->select(
                    'ei.ei_id',
                    'ei.ei_numero_comprobante',
                    'ei.ei_tipo_adquisicion',
                    'ei.ei_id_funcionario',
                    'ei.ei_id_proveedor',
                    'ei.ei_fecha_emision',
                    'ei.ei_fecha_vencimiento',
                    'ei.ei_created_at',
                    'ei.ei_updated_at',
                    'ei.ei_id_user',
                    'ei.ei_detalle_producto',
                    'ei.ei_id_estado',
                    'ei.ei_numero_ingreso',
                    'ei.ei_ruta_comprobante',
                    'p.prov_nombre as proveedor_nombre',
                    'p.prov_ruc',
                    'e.estado as estado_nombre'
                )
                ->where('ei.ei_id_estado', '=', 8)
                ->where('ei.ei_id', '=', $id)
                ->get();
            return response()->json($data, 200);
        } catch (\Exception $e) {
            $this->logController->saveLog('Nombre de Controlador: IngresosControllers, Nombre de Funcion:consultarIngresosId($id)', 'Error al consultar ingresos: ' . $e->getMessage());
            Log::error('Error al consultar ingresos: ' . $e->getMessage());
            return response()->json(['error' => 'Error al consultar ingresos: ' . $e->getMessage()], 500);
        }
    }

    public function guardarIngresosU(Request $request)
    {
        try {
            log::info('data', $request->all());
            $data = $request->all();
            $data = json_decode(file_get_contents('php://input'), true);

            $validator = Validator::make($request->all(), [
                'encabezado.n_comprobante' => 'required|string|max:100',
                'encabezado.tipo_adquisicion' => 'required|integer',
                'encabezado.id_proveedor' => 'required|integer',
                'encabezado.n_ingreso' => 'required|string|max:100',
                'encabezado.fecha_emision' => 'required|date',
                'encabezado.fecha_vencimiento' => 'required|date|after_or_equal:encabezado.fecha_emision',

                'detalleProductos' => 'required|array|min:1',
                'detalleProductos.*.idInsumo' => 'required|integer',
                'detalleProductos.*.nombre' => 'required|string|max:255',
                'detalleProductos.*.cantidad' => 'required|numeric|min:1',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            if ($request->hasFile($data['encabezado']['archivo_comprobante'])) {
                $archivo = $request->file($data['encabezado']['archivo_comprobante']);

                $nombreArchivo = time() . '_' . $archivo->getClientOriginalName();

                $ruta = public_path('Files/comprobantes_ingresos');
                $archivo->move($ruta, $nombreArchivo);

                $id = DB::table('cpu_encabezados_ingresos')->insert([
                    'ei_numero_comprobante' => $data['encabezado']['n_comprobante'],
                    'ei_numero_ingreso' => $data['encabezado']["n_ingreso"],
                    //'ei_id_funcionario' => 1311718181,
                    'ei_id_estado' => 8,
                    'ei_tipo_adquisicion' => $data['encabezado']["tipo_adquisicion"],
                    'ei_id_proveedor' => $data['encabezado']["id_proveedor"],
                    'ei_fecha_emision' => $data['encabezado']["fecha_emision"],
                    'ei_fecha_vencimiento' => $data['encabezado']["fecha_vencimiento"],
                    'ei_created_at' => now(),
                    'ei_updated_at' => now(),
                    'ei_id_user' => $data['encabezado']["id_usuario"],
                    'ei_ruta_comprobante' => $nombreArchivo ?? null,
                    'ei_detalle_producto' => json_encode($data['detalleProductos'])
                ]);
                $id = DB::table('cpu_encabezados_ingresos')->latest('ei_id')->first()->ei_id;

                $data_detalle_producto = $data['detalleProductos'];
                foreach ($data_detalle_producto as $value) {
                    $idInsumo = $value['idInsumo'];
                    $stock_actual_anterior_anterior = DB::table('view_movimientos_inventarios')
                        ->where('mi_id_insumo', $idInsumo)
                        ->orderBy('mi_created_at', 'desc')
                        ->value('mi_stock_actual');

                    $stock_anterior = (int) $stock_actual_anterior_anterior;
                    $cantidad = (int) $value['cantidad'];

                    $stock_actual = $stock_actual_anterior_anterior + $cantidad;

                    DB::table('cpu_movimientos_inventarios')->insert([
                        'mi_id_insumo' => $value['idInsumo'],
                        'mi_cantidad' => $value['cantidad'],
                        'mi_stock_anterior' => $stock_anterior,
                        'mi_stock_actual' => $stock_actual,
                        'mi_tipo_transaccion' => 1,
                        'mi_fecha' => $data['encabezado']["fecha_emision"],
                        'mi_created_at' => now(),
                        'mi_updated_at' => now(),
                        'mi_user_id' => $data['encabezado']["id_usuario"],
                        'mi_id_encabezado' => $id
                    ]);
                    echo "Total de filas: " . count($data_detalle_producto);
                }
                return response()->json(['success' => true, 'message' => 'Activos agregados correctamente', 'id' => $id, 'data_detalle_producto' => $data_detalle_producto], 200);
            }
        } catch (\Exception $e) {
            $this->logController->saveLog('Nombre de Controlador: IngresosControllers, Nombre de Funcion: guardarIngresos(Request $request)', 'Error al guardar ingresos: ' . $e->getMessage());
            Log::error('Error al guardar ingresos: ' . $e->getMessage());
            return response()->json(['error' => 'Error al decodificar el JSON: ' . $e->getMessage()], 400);
        }
    }

    // public function guardarIngresos(Request $request)
    // {
    //     try {
    //         $request->validate([
    //             'encabezado' => 'required|string',
    //             'detalleProductos' => 'required|string',
    //             'archivo_comprobante' => 'nullable|file|mimes:pdf|max:2048',
    //         ]);

    //         $encabezado = json_decode($request->input('encabezado'), true);
    //         $detalleProductos = json_decode($request->input('detalleProductos'), true);

    //         if (!$encabezado || !$detalleProductos) {
    //             return response()->json(['error' => 'Datos JSON inválidos.'], 422);
    //         }

    //         $nombreArchivo = null;
    //         if ($request->hasFile('archivo_comprobante')) {
    //             $archivo = $request->file('archivo_comprobante');
    //             $nombreArchivo = time() . '_' . $archivo->getClientOriginalName();
    //             $ruta = public_path('Files/comprobantes_ingresos');

    //             if (!file_exists($ruta)) {
    //                 mkdir($ruta, 0755, true);
    //             }

    //             $archivo->move($ruta, $nombreArchivo);
    //         }

    //         $id = DB::table('cpu_encabezados_ingresos')->insertGetId([
    //             'ei_numero_comprobante' => $encabezado['n_comprobante'],
    //             'ei_numero_ingreso' => $encabezado['n_ingreso'],
    //             'ei_id_funcionario' => 1,
    //             'ei_tipo_adquisicion' => $encabezado['tipo_adquisicion'],
    //             'ei_id_proveedor' => $encabezado['id_proveedor'],
    //             'ei_fecha_emision' => $encabezado['fecha_emision'],
    //             'ei_fecha_vencimiento' => $encabezado['fecha_vencimiento'],
    //             'ei_created_at' => now(),
    //             'ei_updated_at' => now(),
    //             'ei_id_user' => $encabezado['id_usuario'],
    //             'ei_ruta_comprobante' => $nombreArchivo,
    //             'ei_detalle_producto' => json_encode($detalleProductos)
    //         ], 'ei_id');

    //         foreach ($detalleProductos as $value) {
    //             $idInsumo = $value['idInsumo'];
    //             $cantidad = (int) $value['cantidad'];

    //             $stockAnterior = DB::table('view_movimientos_inventarios')
    //                 ->where('mi_id_insumo', $idInsumo)
    //                 ->orderBy('mi_created_at', 'desc')
    //                 ->value('mi_stock_actual') ?? 0;

    //             $stockNuevo = $stockAnterior + $cantidad;

    //             DB::table('cpu_movimientos_inventarios')->insert([
    //                 'mi_id_insumo' => $idInsumo,
    //                 'mi_cantidad' => $cantidad,
    //                 'mi_stock_anterior' => $stockAnterior,
    //                 'mi_stock_actual' => $stockNuevo,
    //                 'mi_tipo_transaccion' => 1,
    //                 'mi_fecha' => $encabezado['fecha_emision'],
    //                 'mi_created_at' => now(),
    //                 'mi_updated_at' => now(),
    //                 'mi_user_id' => $encabezado['id_usuario'],
    //                 'mi_id_encabezado' => $id,
    //             ]);
    //         }

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Ingreso guardado correctamente.',
    //             'id' => $id
    //         ], 200);
    //     } catch (\Exception $e) {
    //         Log::error('Error al guardar ingreso: ' . $e->getMessage());
    //         $this->logController->saveLog('Nombre de Controlador: IngresosControllers, Nombre de Funcion: guardarIngresos(Request $request)', 'Error al guardar: ' . $e->getMessage());
    //         return response()->json([
    //             'error' => 'Error al guardar: ' . $e->getMessage()
    //         ], 500);
    //     }
    // }

    public function guardarIngresos(Request $request)
    {
        try {
            $request->validate([
                'encabezado' => 'required|string',
                'detalleProductos' => 'required|string',
                'archivo_comprobante' => 'nullable|file|mimes:pdf|max:2048',
            ]);

            $encabezado = json_decode($request->input('encabezado'), true);
            $detalleProductos = json_decode($request->input('detalleProductos'), true);

            if (!$encabezado || !$detalleProductos) {
                return response()->json(['error' => 'Datos JSON inválidos.'], 422);
            }

            $nombreArchivo = null;
            if ($request->hasFile('archivo_comprobante')) {
                $archivo = $request->file('archivo_comprobante');
                $nombreArchivo = time() . '_' . $archivo->getClientOriginalName();
                $ruta = public_path('Files/comprobantes_ingresos');

                if (!file_exists($ruta)) {
                    mkdir($ruta, 0755, true);
                }

                $archivo->move($ruta, $nombreArchivo);
            }

            if (
                !isset($encabezado['id_ingreso']) ||
                $encabezado['id_ingreso'] === null ||
                $encabezado['id_ingreso'] === ''
            ) {
                // INSERTAR NUEVO ENCABEZADO
                $id = DB::table('cpu_encabezados_ingresos')->insertGetId([
                    'ei_numero_comprobante' => $encabezado['n_comprobante'],
                    'ei_numero_ingreso' => $encabezado['n_ingreso'],
                    'ei_id_funcionario' => 1,
                    'ei_tipo_adquisicion' => $encabezado['tipo_adquisicion'],
                    'ei_id_proveedor' => $encabezado['id_proveedor'],
                    'ei_fecha_emision' => $encabezado['fecha_emision'],
                    'ei_fecha_vencimiento' => $encabezado['fecha_vencimiento'],
                    'ei_created_at' => now(),
                    'ei_id_estado' => 8,
                    'ei_updated_at' => now(),
                    'ei_id_user' => $encabezado['id_usuario'],
                    'ei_ruta_comprobante' => $nombreArchivo,
                    'ei_detalle_producto' => json_encode($detalleProductos)
                ], 'ei_id');
            } else {
                // ACTUALIZAR ENCABEZADO EXISTENTE
                $id = $encabezado['id_ingreso'];

                // 1. ELIMINAR MOVIMIENTOS ANTERIORES de ese ingreso
                DB::table('cpu_movimientos_inventarios')
                    ->where('mi_id_encabezado', $id)
                    ->delete();

                // 2. ACTUALIZAR ENCABEZADO
                DB::table('cpu_encabezados_ingresos')
                    ->where('ei_id', $id)
                    ->update([
                        'ei_numero_comprobante' => $encabezado['n_comprobante'],
                        'ei_numero_ingreso' => $encabezado['n_ingreso'],
                        'ei_tipo_adquisicion' => $encabezado['tipo_adquisicion'],
                        'ei_id_proveedor' => $encabezado['id_proveedor'],
                        'ei_fecha_emision' => $encabezado['fecha_emision'],
                        'ei_fecha_vencimiento' => $encabezado['fecha_vencimiento'],
                        'ei_updated_at' => now(),
                        'ei_id_estado' => 8,
                        'ei_id_user' => $encabezado['id_usuario'],
                        'ei_ruta_comprobante' => $nombreArchivo,
                        'ei_detalle_producto' => json_encode($detalleProductos)
                    ]);
            }

            // 3. REINSERTAR MOVIMIENTOS y RECALCULAR STOCK
            foreach ($detalleProductos as $value) {
                $idInsumo = $value['idInsumo'];
                $cantidad = (int) $value['cantidad'];

                // RECONSTRUIR STOCK ACTUAL antes de este ingreso
                $stockAnterior = DB::table('cpu_movimientos_inventarios')
                    ->where('mi_id_insumo', $idInsumo)
                    ->orderBy('mi_created_at', 'desc')
                    ->value('mi_stock_actual') ?? 0;

                $stockNuevo = $stockAnterior + $cantidad;

                DB::table('cpu_movimientos_inventarios')->insert([
                    'mi_id_insumo' => $idInsumo,
                    'mi_cantidad' => $cantidad,
                    'mi_stock_anterior' => $stockAnterior,
                    'mi_stock_actual' => $stockNuevo,
                    'mi_tipo_transaccion' => 1,
                    'mi_fecha' => $encabezado['fecha_emision'],
                    'mi_created_at' => now(),
                    'mi_updated_at' => now(),
                    'mi_user_id' => $encabezado['id_usuario'],
                    'mi_id_encabezado' => $id,
                ]);
            }
            $this->auditar('turnos', 'listarTurnosPorFuncionario', "", "", 'CONSULTA', 'Consulta de turnos por funcionario');

            return response()->json([
                'success' => true,
                'message' => 'Ingreso guardado correctamente.',
                'id' => $id
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error al guardar ingreso: ' . $e->getMessage());
            $this->logController->saveLog('Nombre de Controlador: IngresosControllers, Nombre de Funcion: guardarIngresos(Request $request)', 'Error al guardar: ' . $e->getMessage());
            return response()->json([
                'error' => 'Error al guardar: ' . $e->getMessage()
            ], 500);
        }
    }

    public function guardarIngresosPrueba(Request $request)
    {
        $nombreArchivo = null;
        if ($request->hasFile('archivo_comprobante')) {
            $archivo = $request->file('archivo_comprobante');
            $nombreArchivo = time() . '_' . $archivo->getClientOriginalName();
            $ruta = public_path('Files/comprobantes_ingresos');
            if (!file_exists($ruta)) {
                mkdir($ruta, 0755, true);
            }
            $archivo->move($ruta, $nombreArchivo);
        }
    }

    public function getIdNumeroIngreso()
    {
        $id = 0;
        $ultimoIngreso = DB::table('cpu_encabezados_ingresos')->latest('ei_id')->first();

        if ($ultimoIngreso && !empty($ultimoIngreso->ei_id)) {
            $id = $ultimoIngreso->ei_id;
        } else {
            $id = 0;
        }

        if ($id) {
            $id_numero_ingreso = str_pad($id, 6, '0', STR_PAD_LEFT);
            $id_numero_ingreso = 'ULEAM-DBU-I-' . $id_numero_ingreso + 1;
        } else {
            $id_numero_ingreso = 'ULEAM-DBU-I-000001';
        }
        return $id_numero_ingreso;
    }


    public function getKardexMovimiento()
    {
        try {
            $data = DB::select("
                 SELECT 
            m.mi_id,
            m.mi_id_insumo,
            ins.ins_descripcion AS nombre_insumo,
            m.mi_cantidad,
            m.mi_stock_anterior,
            m.mi_stock_actual,
            m.mi_tipo_transaccion,
            -- Número de comprobante según tipo de transacción
            CASE 
                WHEN m.mi_tipo_transaccion = 1 THEN i.ei_numero_comprobante
                ELSE 'N/A'
            END AS numero_comprobante,
            -- Tipo de movimiento
            CASE 
                WHEN m.mi_tipo_transaccion = 1 THEN 'Ingreso'
                WHEN m.mi_tipo_transaccion = 2 THEN 'Egreso'
                ELSE 'Otro'
            END AS tipo_movimiento,
            CASE 
                WHEN m.mi_tipo_transaccion = 1 THEN i.ei_numero_ingreso
                WHEN m.mi_tipo_transaccion = 2 THEN e.ee_numero_egreso
                ELSE 'N/A'
            END AS numero_ingreso,
            m.mi_fecha,
            m.mi_created_at,
            m.mi_updated_at,
            m.mi_user_id,
            u.name AS nombre_usuario,
            u.email AS email_usuario,
            -- Otros datos de ingresos y egresos opcionales
            i.ei_numero_ingreso,
            i.ei_id_proveedor,
            i.ei_fecha_emision,
            e.ee_id,
            e.ee_id_funcionario,
            e.ee_cedula_funcionario,
            e.ee_id_paciente,
            e.ee_cedula_paciente,
            e.ee_observacion,
            -- Stock acumulado
            SUM(
                CASE 
                    WHEN m.mi_tipo_transaccion = 1 THEN m.mi_cantidad
                    WHEN m.mi_tipo_transaccion = 2 THEN -m.mi_cantidad
                    ELSE 0
                END
            ) OVER (
                PARTITION BY m.mi_id_insumo
                ORDER BY m.mi_fecha, m.mi_created_at
                ROWS BETWEEN UNBOUNDED PRECEDING AND CURRENT ROW
            ) AS stock_actual_acumulado
        FROM cpu_movimientos_inventarios m
        LEFT JOIN cpu_encabezados_ingresos i 
            ON m.mi_id_encabezado = i.ei_id 
            AND m.mi_tipo_transaccion = 1
        LEFT JOIN cpu_encabezados_egresos e 
            ON m.mi_id_encabezado = e.ee_id 
            AND m.mi_tipo_transaccion = 2
        LEFT JOIN cpu_insumo ins
            ON ins.id = m.mi_id_insumo
        LEFT JOIN users u
            ON u.id = m.mi_user_id
        ORDER BY m.mi_id_insumo, m.mi_fecha, m.mi_created_at;
        ");
            return response()->json($data);
        } catch (\Exception $e) {
            $this->logController->saveLog('Nombre de Controlador: IngresosControllers, Nombre de Funcion: getKardexMovimiento()', 'Error al guardar: ' . $e->getMessage());
            return response()->json(['error' => 'Error al obtener movimientos: ' . $e->getMessage()], 500);
        }
    }
}
