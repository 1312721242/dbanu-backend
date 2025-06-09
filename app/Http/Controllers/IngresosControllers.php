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
    }

    public function consultarIngresos()
    {
        $data = DB::select('SELECT * FROM public.view_ingresos');
        return response()->json($data);
    }

     public function guardarIngresos(Request $request)
    {
        log::info('data', $request->all());
        $data = $request->all();

        $data = json_decode(file_get_contents('php://input'), true);

        $validator = Validator::make($request->all(), [
            'encabezado.n_comprobante' => 'required|string|max:100',
            'encabezado.tipo_adquisicion' => 'required|integer',
            'encabezado.id_proveedor' => 'required|integer',
            'encabezado.n_ingreso' => 'required|integer',
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

        $id = DB::table('cpu_encabezados_ingresos')->insert([
            'ei_numero_comprobante' =>$data['encabezado']['n_comprobante'],
            'ei_numero_ingreso' => $data['encabezado']["n_ingreso"],
            'ei_id_funcionario' => 1,
            'ei_tipo_adquisicion' => $data['encabezado']["tipo_adquisicion"],
            'ei_id_proveedor' => $data['encabezado']["id_proveedor"],
            'ei_fecha_emision' => $data['encabezado']["fecha_emision"],
            'ei_fecha_vencimiento' =>$data['encabezado']["fecha_vencimiento"],
            'ei_created_at' => now(),
            'ei_updated_at' => now(),
            'ei_id_user' => $data['encabezado']["id_usuario"],
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
                'mi_id_insumo' =>$value['idInsumo'],
                'mi_cantidad' => $value['cantidad'],
                'mi_stock_anterior' => $stock_anterior,
                'mi_stock_actual' => $stock_actual,
                'mi_tipo_transaccion' => 1,
                'mi_fecha' => $data['encabezado']["fecha_emision"],
                'mi_created_at' => now(),
                'mi_updated_at' => now(),
                'mi_user_id' =>$data['encabezado']["id_usuario"],
                'mi_id_encabezado' => $id 
            ]);
            echo "Total de filas: " . count($data_detalle_producto);
        }
       
        return response()->json(['success' => true, 'message' => 'Activos agregados correctamente']);
    }

    public function getIdNumeroIngreso()
    {
        $id = 0;
        $id = DB::table('cpu_encabezados_ingresos')->latest('ei_id')->first()->ei_id;
       if ($id) {
            $id_numero_ingreso = str_pad($id, 6, '0', STR_PAD_LEFT);
            $id_numero_ingreso = 'ULEAM-DBU-I-' . $id_numero_ingreso;
            Log::info('ID generado: ' . $id_numero_ingreso);
        }else {
            Log::error('No se pudo obtener el ID del último ingreso.');
            $id_numero_ingreso = 1;
        }
        return $id_numero_ingreso;
        
    }

}
