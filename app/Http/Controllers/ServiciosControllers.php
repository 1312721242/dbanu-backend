<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CpuTurno;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class ServiciosControllers extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function __construct()
    {
        $this->middleware('auth:api');
        $this->auditoriaController = new AuditoriaControllers();
        $this->logController = new LogController();
    }

    public function getCategoriaServicio(Request $request)
    {
        try {
            $data = DB::select(
                "SELECT * FROM db_train_revive.cpu_categoria_servicios ORDER BY cat_id"
            );
            $this->logController->saveLog(
                'Nombre de Controlador: ServiciosControllers, Nombre de Función: getCategoriaServicio',
                'Data: ' . json_encode($data)
            );
            return response()->json($data);
        } catch (\Exception $e) {
            $this->logController->saveLog(
                'Nombre de Controlador: ServiciosControllers, Nombre de Función: getCategoriaServicio',
                'Error al listar Servicio: ' . $e->getMessage()
            );

            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getServicioCategoriaId($idCategoria)
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

    public function getTipoServicio(Request $request)
    {
        try {
            $data = DB::select(
                "SELECT * FROM db_train_revive.cpu_tipos_servicios ORDER BY ts_id"
            );
            return response()->json($data);
        } catch (\Throwable $th) {
            $this->logController->saveLog(
                'Nombre de Controlador: ServiciosControllers, Nombre de Función: getTipoServicio(Request $request)',
                'Error al listar tipos de servicio: ' . $th->getMessage()
            );

            return response()->json([
                'error' => $th->getMessage()
            ], 500);
        }
    }


    //    public function getServicioCategoriaId(Request $request)
    // {
    //     $idCategoria = $request->query('idCategoria'); // GET parameter
    //     dd($idCategoria); // <--- prueba si llega
    // }



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
