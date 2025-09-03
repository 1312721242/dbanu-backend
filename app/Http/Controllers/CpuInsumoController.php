<?php

namespace App\Http\Controllers;

use App\Models\CpuInsumo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\URL;
use Session;
use App\Http\Controllers\AuditoriaControllers;

class CpuInsumoController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
        $this->auditoriaController = new AuditoriaControllers();
        $this->logController = new LogController();
    }

    public function getInsumos()
    {
        $insumosMedicos = CpuInsumo::where('id_tipo_insumo', '=', 3)
            ->where('cantidad_unidades', '>=', 1)
            ->orderBy('ins_descripcion', 'asc')
            ->select('id', 'id_tipo_insumo', 'ins_descripcion', 'cantidad_unidades', 'ins_cantidad')
            ->get();

        $medicamentos = CpuInsumo::where('id_tipo_insumo', '=', 2)
            ->where('cantidad_unidades', '>=', 1)
            ->orderBy('ins_descripcion', 'asc')
            ->select('id', 'id_tipo_insumo', 'ins_descripcion', 'cantidad_unidades', 'ins_cantidad')
            ->get();
        $this->auditar('cpu_insumo', 'getInsumos', '', $insumosMedicos, 'CONSULTA', 'Consulta de insumos médicos');
        return response()->json([
            'insumosMedicos' => $insumosMedicos,
            'medicamentos' => $medicamentos
        ]);
    }

    //funcion para auditar
    private function auditar($tabla, $campo, $dataOld, $dataNew, $tipo, $descripcion, $request = null)
    {
        $usuario = $request && !is_string($request) ? $request->user()->name : auth()->user()->name;
        $ip = $request && !is_string($request) ? $request->ip() : request()->ip();
        $ipv4 = gethostbyname(gethostname());
        $publicIp = file_get_contents('https://ifconfig.me/ip');
        $ioConcatenadas = 'IP LOCAL: ' . $ip . '  --IPV4: ' . $ipv4 . '  --IP PUBLICA: ' . $publicIp;
        $nombreequipo = gethostbyaddr($ip);
        $userAgent = $request && !is_string($request) ? $request->header('User-Agent') : request()->header('User-Agent');
        $tipoEquipo = 'Desconocido';

        if (stripos($userAgent, 'Mobile') !== false) {
            $tipoEquipo = 'Celular';
        } elseif (stripos($userAgent, 'Tablet') !== false) {
            $tipoEquipo = 'Tablet';
        } elseif (stripos($userAgent, 'Laptop') !== false || stripos($userAgent, 'Macintosh') !== false) {
            $tipoEquipo = 'Laptop';
        } elseif (stripos($userAgent, 'Windows') !== false || stripos($userAgent, 'Linux') !== false) {
            $tipoEquipo = 'Computador de Escritorio';
        }
        $nombreUsuarioEquipo = get_current_user() . ' en ' . $tipoEquipo;

        $fecha = now();
        $codigo_auditoria = strtoupper($tabla . '_' . $campo . '_' . $tipo);
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

        ]);
    }

    private function getTipoAuditoria($tipo)
    {
        switch ($tipo) {
            case 'CONSULTA':
                return 1;
            case 'INSERCION':
                return 3;
            case 'MODIFICACION':
                return 2;
            case 'ELIMINACION':
                return 4;
            case 'LOGIN':
                return 5;
            case 'LOGOUT':
                return 6;
            case 'DESACTIVACION':
                return 7;
            default:
                return 0;
        }
    }

    public function consultarInsumos()
    {
        $data = DB::select('SELECT * FROM public.view_insumos');
        return response()->json($data);
    }

    public function consultarTiposInsumos()
    {
        $data = DB::select('SELECT * FROM public.view_tipos_insumos');
        return response()->json($data);
    }

    public function consultarInsumosPorTipo($id_tipo_insumo)
    {
        $data = DB::select('SELECT * FROM public.view_insumos WHERE id_tipo_insumo = ?', [$id_tipo_insumo]);
        return response()->json($data);
    }

    // public function saveInsumos(Request $request)
    // {
    //     log::info('data', $request->all());
    //     $data = $request->all();
    //     $userId = $data['id_usuario'];

    //     $validator = Validator::make($request->all(), [
    //         'txt-descripcion' => 'required|string|max:500',
    //         'txt-codigo' => 'required|string|max:500'
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json(['error' => $validator->errors()], 400);
    //     }

    //     $id_in = DB::table('cpu_insumo')->insertGetId([
    //         'id_tipo_insumo' => $data['select-tipo'],
    //         'ins_descripcion' => $data['txt-descripcion'],
    //         'codigo' => $data['txt-codigo'],
    //         'id_estado' => $data['select-estado'],
    //         'unidad_medida' => $data['select-unidad-medida'],
    //         'created_at' => now(),
    //         'updated_at' => now(),
    //         'id_usuario' => $userId,
    //     ]);

    //     $descripcionAuditoria = 'Se guardo el insumo: ' . $data['txt-descripcion'] . ' con codigo: ' . $data['txt-codigo']. ' y ID: ' . $id_in;
    //     $this->auditoriaController->auditar('cpu_insumo', 'saveInsumos', '',json_encode($data), 'INSERT', $descripcionAuditoria);

    //    // $this->auditar('cpu_insumo', 'saveInsumos', '',json_encode($data), 'INSERCION', 'Guardar insumos');

    //     DB::table('cpu_movimientos_inventarios')->insert([
    //             'mi_id_insumo' =>$id_in,
    //             'mi_cantidad' => 0,
    //             'mi_stock_anterior' => 0,
    //             'mi_stock_actual' => $data['select-unidad-medida'],
    //             'mi_tipo_transaccion' => 1,
    //             'mi_fecha' => now(),
    //             'mi_created_at' => now(),
    //             'mi_updated_at' => now(),
    //             'mi_user_id' =>  $userId,
    //             'mi_id_encabezado' => 0
    //         ]);

    //     $id_m = DB::table('cpu_movimientos_inventarios')->latest('mi_id')->first()->mi_id;
    //     $descripcionAuditoria = 'Se guardo insumo: ' . $data['txt-descripcion'] . ' con codigo: ' . $data['txt-codigo']. ' y ID: ' . $id_m;
    //     $this->auditoriaController->auditar('cpu_movimientos_inventarios', 'saveInsumos', '',json_encode($data), 'INSERT', $descripcionAuditoria);

    //     return response()->json(['success' => true, 'message' => 'Insumo agregado correctamente']);
    // }

    public function saveInsumos(Request $request)
    {
        Log::info('Datos recibidos:', $request->all());
        $data = $request->all();
        $userId = $data['id_usuario'] ?? null;

        // Validación
        $validator = Validator::make($request->all(), [
            'txt-descripcion' => 'required|string|max:500',
            'txt-codigo' => 'required|string|max:500',
            'select-tipo' => 'required|integer',
            'select-estado' => 'required|integer',
            'select-unidad-medida' => 'required|string',
            'txt-stock-inicial' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            $this->logController->saveLog(
                'Controlador: InsumosController, Función: saveInsumos()',
                'Error de validación: ' . json_encode($validator->errors())
            );

            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'data' => $validator->errors()
            ], 400);
        }

        // Validar que el código no exista
        $existeCodigo = DB::table('cpu_insumo')
            ->where('codigo', $data['txt-codigo'])
            ->exists();

        if ($existeCodigo) {
            return response()->json([
                'success' => false,
                'message' => 'El código "' . $data['txt-codigo'] . '" ya está registrado'
            ], 200);
        }

        try {
            $descripcionAuditoria = [];

            $id_in = DB::table('cpu_insumo')->insertGetId([
                'id_tipo_insumo' => $data['select-tipo'],
                'ins_descripcion' => $data['txt-descripcion'],
                'codigo' => $data['txt-codigo'],
                'id_estado' => $data['select-estado'],
                'unidad_medida' => $data['select-unidad-medida'],
                'created_at' => now(),
                'updated_at' => now(),
                'id_usuario' => $userId,
            ]);

            $descripcionAuditoria[] = 'Se guardó el insumo: "' . $data['txt-descripcion'] .
                '" con código: "' . $data['txt-codigo'] . '" (ID Insumo: ' . $id_in . ')';

            $id_m = DB::table('cpu_movimientos_inventarios')->insertGetId([
                'mi_id_insumo' => $id_in,
                'mi_cantidad' => 0,
                'mi_stock_anterior' => 0,
                'mi_stock_actual' => $data['txt-stock-inicial'],
                'mi_tipo_transaccion' => 1,
                'mi_fecha' => now(),
                'mi_created_at' => now(),
                'mi_updated_at' => now(),
                'mi_user_id' => $userId,
                'mi_id_encabezado' => 0
            ], 'mi_id');

            $descripcionAuditoria[] = 'Se creó movimiento de inventario inicial (ID Movimiento: ' . $id_m . ')';

            // Auditoría unificada
            $this->auditoriaController->auditar(
                'cpu_insumo & cpu_movimientos_inventarios',
                'saveInsumos',
                '',
                json_encode($data),
                'INSERT',
                implode(' | ', $descripcionAuditoria)
            );

            return response()->json([
                'success' => true,
                'message' => 'Insumo agregado correctamente',
                'data' => ['id' => $id_in]
            ]);
        } catch (\Exception $e) {
            $this->logController->saveLog(
                'Controlador: InsumosController, Función: saveInsumos()',
                'Error al guardar insumo: ' . $e->getMessage()
            );

            return response()->json([
                'success' => false,
                'message' => 'Hubo un problema al guardar el insumo: ' . $e->getMessage()
            ], 500);
        }
    }


    public function modificarInsumo(Request $request, $id)
    {
        log::info('data', $request->all());
        $data = $request->all();
        $userId = Session::get('user_id');

        $validator = Validator::make($request->all(), [
            'txt-descripcion' => 'required|string|max:500',
            'txt-codigo' => 'required|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $facultadNombre = $request->input('fac_nombre');
        $usuario = $request->user()->name;
        $ip = $request->ip();
        $nombreequipo = gethostbyaddr($ip);
        $fecha = now();


        $id_in = DB::table('cpu_insumo')
            ->where('id', $id)
            ->update([
                'id_tipo_insumo' => $data['select-tipo'],
                'ins_descripcion' => $data['txt-descripcion'],
                'codigo' => $data['txt-codigo'],
                'id_estado' => $data['select-estado'],
                'unidad_medida' => $data['select-unidad-medida'],
                'updated_at' => now(),
                'id_usuario' => $userId
            ]);

        $descripcionAuditoria = 'Se modifico el insumo: ' . $data['txt-descripcion'] . ' con codigo: ' . $data['txt-codigo'] . ' y ID: ' . $id;
        $this->auditoriaController->auditar('cpu_insumo', 'saveInsumos', '', json_encode($data), 'INSERT', $descripcionAuditoria);;

        return response()->json(['success' => true, 'message' => 'Insumo modificado correctamente']);
    }
}
