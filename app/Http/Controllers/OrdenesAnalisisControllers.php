<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class OrdenesAnalisisControllers extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function ConsultarOrdenAnalisis()
    {
        $data = DB::select('SELECT * FROM public.view_ordenes_analisis');
        return response()->json($data);
    }

    public function ConsultarOrdenAnalisisCedula($cedula)
    {
        $data = DB::select('SELECT * FROM public.view_ordenes_analisis WHERE cedula = ?', [$cedula]);
        return response()->json($data);
    }

    public function GuardarOrdenAnalisis(Request $request)
    {
        log::info('data Orden de Analisis', $request->all());

        $data = $request->all();

        /*$validator = Validator::make($request->all(), [
            'encabezado.n_comprobante' => 'required|string|max:100',
            'encabezado.tipo_adquisicion' => 'required|integer',
            'encabezado.id_proveedor' => 'required|integer',
            'encabezado.fecha_emision' => 'required|date',
            'encabezado.fecha_vencimiento' => 'required|date|after_or_equal:encabezado.fecha_emision',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }*/

       

        $id = DB::table('cpu_ordenes_analisis')->insert([
            'oa_cedula' =>$data['datosOrden']['cedula'],
            'oa_id_paciente' =>$data['datosOrden']['id_paciente'],
            'oa_id_estado' => 8,
            'oa_detalle_orden_analisis' => json_encode($data['detalleEstudios']),
            'oa_created_at' => now(),
            'oa_updated_at' => now(),
        ]);

        $id = DB::table('cpu_ordenes_analisis')->latest('oa_id')->first()->oa_id;

        return response()->json(['success' => true, 'message' => 'Orden de análisis guardada exitosamente']);
    }
}
