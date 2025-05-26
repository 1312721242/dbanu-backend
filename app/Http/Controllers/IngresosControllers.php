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
            'encabezado.fecha_emision' => 'required|date',
            'encabezado.fecha_vencimiento' => 'required|date|after_or_equal:encabezado.fecha_emision',

            'detalleProductos' => 'required|array|min:1',
            'detalleProductos.*.idProducto' => 'required|integer',
            'detalleProductos.*.nombre' => 'required|string|max:255',
            'detalleProductos.*.cantidad' => 'required|numeric|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $id = DB::table('cpu_encabezados_ingresos')->insert([
            'ei_numero_comprobante' =>$data['encabezado']['n_comprobante'],
            'ei_id_funcionario' => 1,
            'ei_tipo_adquisicion' => $data['encabezado']["tipo_adquisicion"],
            'ei_id_proveedor' => $data['encabezado']["id_proveedor"],
            'ei_fecha_emision' => $data['encabezado']["fecha_emision"],
            'ei_fecha_vencimiento' =>$data['encabezado']["fecha_vencimiento"],
            'ei_created_at' => now(),
            'ei_updated_at' => now(),
            'ei_id_user' => 1,
            'ei_detalle_producto' => json_encode($data['detalleProductos'])
        ]);

        $id = DB::table('cpu_encabezados_ingresos')->latest('ei_id')->first()->ei_id;
        
        $data_detalle_producto = $data['detalleProductos'];

        

        $stock_anterior =  DB::select('SELECT mi_stock_anterior FROM public.view_movimientos_inventarios');

        foreach ($data_detalle_producto as $value) {
            $idProducto = $value['idProducto'];

            $stock_actual_anterior_anterior = DB::table('view_movimientos_inventarios')
            ->where('mi_id_producto', $idProducto)
            ->orderBy('mi_created_at', 'desc')
            ->value('mi_stock_actual');

            $stock_anterior = (int) $stock_actual_anterior_anterior;
            $cantidad = (int) $value['cantidad'];

            $stock_actual = $stock_actual_anterior_anterior + $cantidad;

            $id = DB::table('cpu_movimientos_inventarios')->insert([
                'mi_id_producto' =>$value['idProducto'],
                'mi_cantidad' => $value['cantidad'],
                'mi_stock_anterior' => $stock_anterior,
                'mi_stock_actual' => $stock_actual,
                'mi_tipo_transaccion' => 1,
                'mi_fecha' => $data['encabezado']["fecha_emision"],
                'mi_created_at' => now(),
                'mi_updated_at' => now(),
                'mi_user_id' => 1,
                'mi_id_encabezado' => $id 
            ]);
        } 
        
        /*$fecha = now();
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

        ]);*/
        return response()->json(['success' => true, 'message' => 'Activos agregados correctamente']);
    }

}
