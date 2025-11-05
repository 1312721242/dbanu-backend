<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Session;
use Carbon\Carbon;
use Auth;

use Illuminate\Http\Request;

class CpuCategoriaServicioControllers extends Controller
{

    public function __construct()
    {
        $this->middleware('auth:api');
        $this->auditoriaController = new AuditoriaControllers();
        $this->logController = new LogController();
    }

    public function getCategoriaServicio(Request $request)
    {
        try {
            $estado = 8;

            $data = DB::select(
                'SELECT * FROM db_train_revive.cpu_categoria_servicios WHERE cat_id_estado = ?',
                [$estado]
            );

            return response()->json($data);
        } catch (\Exception $e) {
            $this->logController->saveLog(
                'Nombre de Controlador: CpuCategoriaServicioControllers, Nombre de Función: getCategoriaServicio',
                'Error al listar categoria: ' . $e->getMessage()
            );

            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getCategoriaServicioId($idCategoria)
    {
        try {
            $data = DB::select(
                "SELECT * FROM db_train_revive.cpu_tipos_servicios WHERE ts_id_categoria = ? ORDER BY ts_id",
                [$idCategoria]
            );

            return response()->json($data);
        } catch (\Exception $e) {
            $this->logController->saveLog(
                'Nombre de Controlador: ServiciosControllers, Nombre de Función: getServicioCategoriaId($idCategoria)',
                'Error al listar tipos de servicio: ' . $e->getMessage()
            );
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // public function getCategoriaServicioId($id_categoria)
    // {
    //     try {
    //         $data = DB::select(
    //             'SELECT * FROM db_train_revive.cpu_categoria_servicios WHERE cat_id = ?',
    //             [$id_categoria]
    //         );

    //         if (empty($data)) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'No se encontró la categoría solicitada.'
    //             ], 404);
    //         }

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Categoría encontrada correctamente.',
    //             'data' => $data[0]
    //         ], 200);
    //     } catch (\Exception $e) {
    //         $this->logController->saveLog(
    //             'Nombre de Controlador: ServiciosControllers, Nombre de Función:  getCategoriaServicioId($id_categoria)',
    //             'Error al listar Servicio: ' . $e->getMessage()
    //         );

    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Error al obtener la categoría de servicio.',
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }


    // public function getCategoriaServicioId($id_categoria)
    // {
    //     try {
    //         $data = DB::select(
    //             'SELECT * FROM db_train_revive.cpu_categoria_servicios WHERE cat_id = ?',
    //             [$id_categoria]
    //         );

    //         return response()->json($data);
    //     } catch (\Exception $e) {
    //         $this->logController->saveLog(
    //             'Nombre de Controlador: ServiciosControllers, Nombre de Función:  getCategoriaServicioId($id_categoria)',
    //             'Error al listar Servicio: ' . $e->getMessage()
    //         );

    //         return response()->json([
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }

    public function guardarCategoriaServicio(Request $request)
{
    try {
        $rules = [
            'modo' => 'required|string|in:INSERT,UPDATE',
            'txt_nombre' => 'required|string|max:255',
            'txt_descripcion' => 'nullable|string',
            'select_id_estado' => 'required|integer',
        ];

        if ($request->input('modo') === 'UPDATE') {
            $rules['cat_id'] = 'required|integer';
        }

        $validated = $request->validate($rules);

        DB::beginTransaction();

        $userId = Auth::user()->id ?? null;
        $now = Carbon::now();
        $modo = strtoupper($request->input('modo'));
        $conexion = 'pgsql'; 

        if ($modo === 'INSERT') {
            $idCategoria = DB::connection($conexion)
                ->table(DB::raw('db_train_revive.cpu_categoria_servicios'))
                ->insertGetId([
                    'cat_nombre' => $request->input('txt_nombre'),
                    'cat_descripcion' => $request->input('txt_descripcion'),
                    'cat_id_estado' => $request->input('select_id_estado'),
                    'cat_id_user' => $userId,
                    'cat_created_at' => $now,
                    'cat_updated_at' => $now,
                ], 'cat_id');

            $descripcionAuditoria = sprintf(
                "INSERT categoría servicio (ID: %d). Datos: nombre='%s', descripcion='%s', id_estado=%d, creado_por=%s, fecha=%s",
                $idCategoria,
                $request->input('txt_nombre'),
                $request->input('txt_descripcion'),
                $request->input('select_id_estado'),
                $userId,
                $now->toDateTimeString()
            );

            $this->auditoriaController->auditar(
                'cpu_categoria_servicios',
                'guardarCategoriaServicio',
                '',
                $request->all(),
                'INSERT',
                $descripcionAuditoria
            );

            DB::commit();

            return response()->json([
                'message' => 'Categoría de servicio guardada exitosamente.',
                'cat_id'  => $idCategoria
            ], 201);
        } else {
            $catId = $request->input('cat_id');

            $registroPrevio = DB::connection($conexion)
                ->table(DB::raw('db_train_revive.cpu_categoria_servicios'))
                ->where('cat_id', $catId)
                ->first();

            if (!$registroPrevio) {
                DB::rollBack();
                return response()->json(['error' => 'Categoría no encontrada.'], 404);
            }

            $datosActualizados = [
                'cat_nombre'      => $request->input('txt_nombre'),
                'cat_descripcion' => $request->input('txt_descripcion'),
                'cat_id_estado'   => $request->input('select_id_estado'),
                'cat_updated_at'  => $now,
            ];

            DB::connection($conexion)
                ->table(DB::raw('db_train_revive.cpu_categoria_servicios'))
                ->where('cat_id', $catId)
                ->update($datosActualizados);

            $descripcionAuditoria = sprintf(
                "UPDATE categoría servicio (ID: %d). Antes: nombre='%s', descripcion='%s', id_estado=%s, actualizado_por=%s. Después: nombre='%s', descripcion='%s', id_estado=%d, fecha=%s",
                $catId,
                $registroPrevio->cat_nombre ?? 'NULL',
                $registroPrevio->cat_descripcion ?? 'NULL',
                $registroPrevio->cat_id_estado ?? 'NULL',
                $userId,
                $datosActualizados['cat_nombre'],
                $datosActualizados['cat_descripcion'],
                $datosActualizados['cat_id_estado'],
                $now->toDateTimeString()
            );

            $this->auditoriaController->auditar(
                'cpu_categoria_servicios',
                'guardarCategoriaServicio',
                '',
                [
                    'antes' => (array)$registroPrevio,
                    'despues' => $datosActualizados,
                    'request' => $request->all()
                ],
                'UPDATE',
                $descripcionAuditoria
            );

            DB::commit();

            return response()->json([
                'message' => 'Categoría de servicio actualizada correctamente.',
                'cat_id'  => $catId
            ], 200);
        }
    } catch (\Illuminate\Validation\ValidationException $ve) {
        DB::rollBack();
        return response()->json([
            'error' => $ve->errors()
        ], 422);
    } catch (\Exception $e) {
        DB::rollBack();

        try {
            DB::connection('pgsql')
                ->table(DB::raw('db_train_revive.cpu_log'))
                ->insert([
                    'log_controller' => 'ServiciosController - guardarCategoriaServicio',
                    'log_action'     => $request->input('modo') ?? 'N/A',
                    'log_message'    => $e->getMessage(),
                    'log_data'       => json_encode($request->all()),
                    'log_created_at' => Carbon::now(),
                ]);
        } catch (\Exception $logEx) {}

        $this->logController->saveLog(
            'ServiciosController - guardarCategoriaServicio',
            'Error: ' . $e->getMessage()
        );

        return response()->json([
            'error' => 'Error al guardar categoría de servicio: ' . $e->getMessage()
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
