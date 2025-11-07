<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Carbon\Carbon;


class RuletaController extends Controller
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

    public function cargarExcel()
    {
        try {
            // Ruta del archivo Excel
            $filePath = 'C:\Users\DBANU-ATENCION\Documents\ULEAM\Desarrollo\Ruleta\personas.xlsx';

            // Cargar el archivo
            $spreadsheet = IOFactory::load($filePath);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();

            // Asumiendo que la primera fila es encabezado (cedula, nombres)
            $header = array_shift($rows);

            $insertData = [];
            foreach ($rows as $row) {
                $cedula = trim($row[0]);
                $nombres = trim($row[1]);

                if (!$cedula || !$nombres) {
                    continue;
                }

                $insertData[] = [
                    'cedula' => $cedula,
                    'nombres' => $nombres,
                    'id_estado' => 8,
                ];
            }

            // Guardar en base de datos
            if (!empty($insertData)) {
                DB::table('db_ruleta_premio.tbl_personas')->insert($insertData);
            }

            return response()->json([
                'success' => true,
                'message' => 'Archivo procesado y registros guardados correctamente.',
                'registros' => count($insertData),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getSorteo()
    {
        try {
            $id_usuario = auth()->user()->id;

            $ganador = DB::select(
                "SELECT * FROM db_ruleta_premio.fn_sorteo(?)",
                [$id_usuario]
            );

            if (empty($ganador)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo obtener un ganador del sorteo'
                ], 404);
            }

            $ganador = $ganador[0];

            $descripcionAuditoria = sprintf(
                'Se realizó el sorteo y se registró el ganador "%s" (Cédula: %s) con el premio "%s" el %s.',
                $ganador->out_nombres,
                $ganador->out_cedula,
                $ganador->out_nombre_premio,
                Carbon::now()->toDateTimeString()
            );

            $this->auditoriaController->auditar(
                'db_ruleta_premio.tbl_ganadores',
                'realizarSorteo',
                '',
                (array) $ganador,
                'INSERT',
                $descripcionAuditoria
            );

            return response()->json([
                'success' => true,
                'message' => 'Sorteo realizado correctamente',
                'data' => $ganador
            ], 200);
        } catch (\Exception $e) {
            $this->logController->saveLog(
                'Nombre de Controlador: RuletaController, Función: realizarSorteo()',
                'Error al ejecutar el sorteo',
                [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]
            );

            return response()->json([
                'success' => false,
                'message' => 'Error al realizar el sorteo: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getRepetirSorteo($idPremio)
    {
        try {

            $id_usuario = auth()->user()->id;

            $ganador = DB::select(
                "SELECT * FROM db_ruleta_premio.fn_repetir_sorteo(?, ?)",
                [$idPremio, $id_usuario]
            );

            if (empty($ganador)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo obtener un nuevo ganador'
                ], 404);
            }

            $ganador = $ganador[0];

            $descripcionAuditoria = sprintf(
                'Se repitió el sorteo para el premio "%s" y se registró el ganador "%s" (Cédula: %s) el %s.',
                $ganador->out_nombre_premio,
                $ganador->out_nombres,
                $ganador->out_cedula,
                Carbon::now()->toDateTimeString()
            );

            $this->auditoriaController->auditar(
                'db_ruleta_premio.tbl_ganadores',
                'repetirSorteo',
                '',
                (array) $ganador,
                'INSERT',
                $descripcionAuditoria
            );

            return response()->json([
                'success' => true,
                'message' => 'Sorteo repetido correctamente',
                'data' => $ganador
            ], 200);
        } catch (\Exception $e) {
            $this->logController->saveLog(
                'Nombre de Controlador: RuletaController, Función: repetirSorteo()',
                'Error al repetir el sorteo',
                [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]
            );

            return response()->json([
                'success' => false,
                'message' => 'Error al repetir el sorteo: ' . $e->getMessage()
            ], 500);
        }
    }


    public function getHistorialSorteo()
    {
        try {
            $data = DB::table('db_ruleta_premio.tbl_ganadores as g')
                ->join('db_ruleta_premio.tbl_personas as p', 'p.id_persona', '=', 'g.id_persona')
                ->join('db_ruleta_premio.tbl_premios as pr', 'pr.id_premio', '=', 'g.id_premio')
                ->select(
                    'g.id_ganador',
                    'p.nombres',
                    'p.cedula',
                    'pr.id_premio',
                    'pr.nombre_premio',
                    'g.fecha_ganado',
                    'g.estado_entregado',
                    'g.created_at',
                    'g.updated_at'
                )
                ->where('g.estado_entregado', true)  // Solo los entregados
                ->orderByDesc('g.fecha_ganado')     // Último registro primero
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Historial de sorteos obtenido correctamente',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            $this->logController->saveLog(
                'Nombre de Controlador: RuletaController, Función: getHistorialSorteo()',
                'Error al obtener historial de sorteos',
                ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener historial de sorteos: ' . $e->getMessage()
            ], 500);
        }
    }


    public function actualizarEntrega(Request $request)
    {
        try {
            $request->validate([
                'id_ganador' => 'required|integer|exists:db_ruleta_premio.tbl_ganadores,id_ganador',
                'estado_entregado' => 'required|boolean'
            ]);

            $ganador = DB::table('db_ruleta_premio.tbl_ganadores')
                ->where('id_ganador', $request->id_ganador)
                ->first();

            if (!$ganador) {
                return response()->json(['success' => false, 'message' => 'Ganador no encontrado'], 404);
            }

            // Actualizar estado de entrega
            DB::table('db_ruleta_premio.tbl_ganadores')
                ->where('id_ganador', $request->id_ganador)
                ->update([
                    'estado_entregado' => $request->estado_entregado,
                    'updated_at' => now()
                ]);

            // Si se marca como no entregado, devolver premio
            if ($request->estado_entregado === false) {
                DB::table('db_ruleta_premio.tbl_premios')
                    ->where('id_premio', $ganador->id_premio)
                    ->increment('cantidad_disponible', 1);
            }

            $descripcionAuditoria = sprintf(
                'Se actualizó el estado de entrega del ganador ID %d a %s el %s',
                $request->id_ganador,
                $request->estado_entregado ? 'ENTREGADO' : 'NO ENTREGADO',
                \Carbon\Carbon::now()->toDateTimeString()
            );

            $this->auditoriaController->auditar(
                'db_ruleta_premio.tbl_ganadores',
                'actualizarEntrega',
                (array) $ganador,
                ['estado_entregado' => $request->estado_entregado],
                'UPDATE',
                $descripcionAuditoria
            );

            return response()->json([
                'success' => true,
                'message' => 'Estado de entrega actualizado correctamente'
            ]);
        } catch (\Exception $e) {
            $this->logController->saveLog(
                'Controlador: RuletaController, Función: actualizarEntrega()',
                'Error al actualizar entrega',
                ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar entrega: ' . $e->getMessage()
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
