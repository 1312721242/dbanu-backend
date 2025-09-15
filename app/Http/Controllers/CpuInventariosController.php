<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Session;

class CpuInventariosController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
        $this->auditoriaController = new AuditoriaControllers();
        $this->logController = new LogController();
    }

    public function guardarMovimientoInventario(
        $detalleProductos,
        $idBodega,
        $tipo,
        $estado_movimiento,
        $userId,
        $idEncabezado
    ) {
        DB::beginTransaction();
        $descripcionAuditoria = [];

        try {
            foreach ($detalleProductos as $item) {
                $idInsumo = $item['idInsumo'];
                $cantidad = (int) $item['cantidad'];

                // 1. CONSULTAR STOCK ACTUAL DESDE cpu_stock_bodegas
                $stockBodega = DB::table('cpu_stock_bodegas')
                    ->where('sb_id_bodega', $idBodega)
                    ->where('sb_id_insumo', $idInsumo)
                    ->first();

                $stockAnterior = $stockBodega ? (int) $stockBodega->sb_cantidad : 0;

                // 2. CALCULAR STOCK ACTUAL SEGÚN TIPO YA SEA INGRESO / EGRESO
                if (strtoupper($tipo) === 'INGRESO') {
                    $stockActual = $stockAnterior + $cantidad;
                    $tipoTransaccion = 1;
                    $descripcionAuditoria[] = "Ingreso de {$cantidad} unidades del insumo ID {$idInsumo} en bodega {$idBodega}";
                } elseif (strtoupper($tipo) === 'EGRESO') {
                    if ($stockAnterior < $cantidad) {
                        throw new \Exception("Stock insuficiente para egresar {$cantidad} unidades del insumo ID {$idInsumo}");
                    }
                    $stockActual = $stockAnterior - $cantidad;
                    $tipoTransaccion = 2;
                    $descripcionAuditoria[] = "Egreso de {$cantidad} unidades del insumo ID {$idInsumo} en bodega {$idBodega}";
                } else {
                    throw new \Exception("Tipo de movimiento inválido: {$tipo}");
                }

                // 3. ACTUALIZAR TABLA DE STOCK
                if ($stockBodega) {
                    DB::table('cpu_stock_bodegas')
                        ->where('sb_id', $stockBodega->sb_id)
                        ->update([
                            'sb_cantidad' => $stockActual,
                        ]);
                    $descripcionAuditoria[] = "Actualización de stock en bodega {$idBodega}, insumo ID {$idInsumo}: de {$stockAnterior} a {$stockActual}";
                } else {
                    DB::table('cpu_stock_bodegas')->insert([
                        'sb_id_bodega' => $idBodega,
                        'sb_id_insumo' => $idInsumo,
                        'sb_cantidad' => $stockActual,
                        'sb_stock_minimo' => 5,
                    ]);
                    $descripcionAuditoria[] = "Nuevo registro de stock en bodega {$idBodega}, insumo ID {$idInsumo}, cantidad inicial {$stockActual}";
                }

                // 4. INSERTAR MOVIMIENTO HISTÓRICO
                DB::table('cpu_movimientos_inventarios')->insert([
                    'mi_id_insumo' => $idInsumo,
                    'mi_cantidad' => $cantidad,
                    'mi_stock_anterior' => $stockAnterior,
                    'mi_stock_actual' => $stockActual,
                    'mi_tipo_transaccion' => $tipoTransaccion,
                    'mi_user_id' => $userId,
                    'mi_id_encabezado' => $idEncabezado,
                    'mi_id_bodega' => $idBodega,
                    'mi_id_estado' => $estado_movimiento,
                    'mi_fecha' => now(),
                    'mi_created_at' => now(),
                    'mi_updated_at' => now(),
                ]);
                $descripcionAuditoria[] = "Movimiento histórico registrado: Insumo {$idInsumo}, cantidad {$cantidad}, stock de {$stockAnterior} a {$stockActual}";
            }

            DB::commit();

            //AUDITORÍA
            $this->auditoriaController->auditar(
                'cpu_movimientos_inventarios',
                'guardarMovimientoInventario()',
                json_encode([]),
                json_encode($detalleProductos),
                strtoupper($tipo),
                implode(' | ', $descripcionAuditoria)
            );
        } catch (\Exception $e) {
            DB::rollBack();
            //LOG DE ERRORES
            $this->logController->saveLog(
                'Controlador: IngresosController, Función: guardarMovimientoInventario()',
                'Error al guardar: ' . $e->getMessage()
            );

            throw $e;
        }
    }

    public function guardarInventarioInicial(Request $request)
    {
        try {
            // Parámetros de búsqueda
            $idBodega = 3;
            $idSede = 2;
            $idFacultad = 1;

            $existeInventario = DB::table('cpu_stock_bodegas as sb')
                ->join('cpu_bodegas as b', 'b.bod_id', '=', 'sb.sb_id_bodega')
                ->where('b.bod_id', $request->input('select_bodega'))
                ->where('b.bod_id_sede', $request->input('select_sede'))
                ->where('b.bod_id_facultad', $request->input('select_facultad'))
                ->exists(); 

            if ($existeInventario) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'El inventario inicial ya ha sido registrado un movimiento anteriormente.'
                    ],
                    400
                );
            }

            $sb_id = DB::table('cpu_stock_bodegas')
                ->insertGetId([
                    'sb_id_bodega'    => $request->input('select_bodega'),
                    'sb_id_insumo'    => $request->input('id_insumo'),
                    'sb_cantidad'     => $request->input('txt-stock-inicial'),
                    'sb_stock_minimo' => $request->input('txt-stock-minimo'),
                    'sb_created_at'   => now(),
                    'sb_updated_at'   => now(),
                    'sb_id_user'      => Session::get('user_id'),
                ], 'sb_id');

            $descripcionAuditoria = "Inventario inicial: Id{$$sb_id} Insumo ID {$request->input('id_insumo')}, Cantidad {$request->input('txt-stock-inicial')}, Bodega ID {$request->input('select_bodega')}";
            $this->auditoriaController->auditar(
                'cpu_stock_bodegas',
                'guardarInventarioInicial()',
                json_encode([]),
                json_encode($request->all()),
                'INSERT',
                $descripcionAuditoria
            );

            return response()->json([
                'success' => true,
                'message' => 'Inventario inicial guardado exitosamente.',
                'id' => $sb_id
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error al guardar inventario inicial: ' . $e->getMessage());
            $this->logController->saveLog('Controlador: CpuInventariosController, Función: guardarInventarioInicial()', 'Error al guardar inventario inicial: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al guardar inventario inicial'], 500);
        }
    }

    public function getStockBodegaInsumoId($id)
    {
        try {
            $data = DB::select("
                SELECT 
                    sb.sb_id,
                    sb.sb_cantidad AS stock_bodega,
                    sb.sb_stock_minimo,
                    sb.sb_id_bodega,
                    b.bod_nombre AS nombre_bodega,
                    b.bod_id_sede,
                    s.nombre_sede,
                    b.bod_id_facultad,
                    f.fac_nombre,
                    i.id AS id_insumo,
                    i.codigo,
                    i.ins_descripcion,
                    i.id_tipo_insumo,
                    i.estado_insumo,
                    i.id_estado,
                    e.estado,
                    i.modo_adquirido
                FROM cpu_stock_bodegas sb
                JOIN cpu_bodegas b ON b.bod_id = sb.sb_id_bodega
                LEFT JOIN cpu_sede s ON s.id = b.bod_id_sede
                LEFT JOIN cpu_facultad f ON f.id = b.bod_id_facultad
                JOIN cpu_insumo i ON i.id = sb.sb_id_insumo
                JOIN cpu_estados e ON e.id = i.id_estado
                WHERE i.id_estado = :estado
                AND i.id = :id_insumo
                ORDER BY i.id DESC
                ", [
                'estado' => 8,
                'id_insumo' => $id
            ]);
            return response()->json($data);
        } catch (\Exception $e) {
            Log::error('Error al obtener insumo por ID: ' . $e->getMessage());
            $this->logController->saveLog(
                'Controlador: InsumosController, Función:  getStockBodegaInsumoId($id)',
                'Error de validación: ' . json_encode($e->getMessage())
            );
            return response()->json(['error' => 'Error al obtener insumo'], 500);
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
