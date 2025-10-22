<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Session;
use Carbon;
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
                'Nombre de Controlador: ServiciosControllers, Nombre de Función: getCategoriaServicio',
                'Error al listar Servicio: ' . $e->getMessage()
            );

            return response()->json([
                'error' => $e->getMessage()
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
