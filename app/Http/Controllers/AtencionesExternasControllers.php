<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Session;

class AtencionesExternasControllers extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
        $this->auditoriaController = new AuditoriaControllers();
        $this->logController = new LogController();
    }

    public function getAtencionesExternas()
    {
        try {
            $data = DB::table('cpu_atenciones')
                ->select(
                    'id',
                    'id_funcionario',
                    'id_persona',
                    'via_atencion',
                    'motivo_atencion',
                    'fecha_hora_atencion',
                    'anio_atencion',
                    'created_at',
                    'updated_at',
                    'detalle_atencion',
                    'id_caso',
                    'id_tipo_usuario',
                    'evolucion_enfermedad',
                    'diagnostico',
                    'prescripcion',
                    'recomendacion',
                    'tipo_atencion',
                    'id_cie10',
                    'id_estado'
                )
                ->where('id_tipo_atencion', 'EXTERNA')
                ->get();

            // Retorna 200 siempre, incluso si no hay registros
            return response()->json($data, 200);
        } catch (\Exception $e) {
            $this->logController->saveLog(
                'Controlador: AtencionesExternasControllers, Función: getAtencionesExternas()',
                'Error al consultar Atenciones Externas: ' . $e->getMessage()
            );
            return response()->json(['message' => 'Error al consultar atenciones externas'], 500);
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
