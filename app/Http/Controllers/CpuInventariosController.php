<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
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
        $userId,
        $idEncabezado
    ) {
        DB::beginTransaction();
        try {
            foreach ($detalleProductos as $item) {
                $idInsumo = $item['idInsumo'];
                $cantidad = (int) $item['cantidad'];

                // === 1. CONSULTAR STOCK ACTUAL DESDE cpu_stock_bodegas ===
                $stockBodega = DB::table('cpu_stock_bodegas')
                    ->where('sb_id_bodega', $idBodega)
                    ->where('sb_id_insumo', $idInsumo)
                    ->first();

                $stockAnterior = $stockBodega ? (int) $stockBodega->sb_cantidad : 0;

                // === 2. CALCULAR STOCK ACTUAL SEGÚN TIPO ===
                if (strtoupper($tipo) === 'INGRESO') {
                    $stockActual = $stockAnterior + $cantidad;
                    $tipoTransaccion = 1;
                } elseif (strtoupper($tipo) === 'EGRESO') {
                    if ($stockAnterior < $cantidad) {
                        throw new \Exception("Stock insuficiente para egresar {$cantidad} unidades del insumo ID {$idInsumo}");
                    }
                    $stockActual = $stockAnterior - $cantidad;
                    $tipoTransaccion = 2;
                } else {
                    throw new \Exception("Tipo de movimiento inválido: {$tipo}");
                }

                // === 3. ACTUALIZAR TABLA DE STOCK ===
                if ($stockBodega) {
                    DB::table('cpu_stock_bodegas')
                        ->where('sb_id', $stockBodega->sb_id)
                        ->update([
                            'sb_cantidad' => $stockActual,
                        ]);
                } else {
                    DB::table('cpu_stock_bodegas')->insert([
                        'sb_id_bodega'   => $idBodega,
                        'sb_id_insumo'   => $idInsumo,
                        'sb_cantidad'    => $stockActual,
                        'sb_stock_minimo' => 5,
                    ]);
                }

                // === 4. INSERTAR MOVIMIENTO HISTÓRICO ===
                DB::table('cpu_movimientos_inventarios')->insert([
                    'mi_id_insumo'        => $idInsumo,
                    'mi_cantidad'         => $cantidad,
                    'mi_stock_anterior'   => $stockAnterior,
                    'mi_stock_actual'     => $stockActual,
                    'mi_tipo_transaccion' => $tipoTransaccion,
                    'mi_user_id'          => $userId,
                    'mi_id_encabezado'    => $idEncabezado,
                    'mi_id_bodega'        => $idBodega,
                    'mi_fecha'            => now(),
                    'mi_created_at'       => now(),
                    'mi_updated_at'       => now(),
                ]);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
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
