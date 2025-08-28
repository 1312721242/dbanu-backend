<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\CpuCarrera;

class CpuCarreraController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
        $this->auditoriaController = new AuditoriaControllers();
        $this->logController = new LogController();
    }


    public function agregarCarrera(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'select-sede' => 'required|integer',
            'select-facultad' => 'required|integer',
            'txt-carrera' => 'required|string',
        ]);

        $fecha = now();

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        try {
            $idFacultad = $request->input('select-facultad');
            $nombreCarrera = $request->input('txt-carrera');

            $usuario = $request->user()->name ?? 'Sistema';

            $id = DB::table('cpu_carrera')->insertGetId([
                'id_facultad' => $idFacultad,
                'id_estado' => $request->input('select-estado'),
                'id_user' => $request->user()->id,
                'name' => $nombreCarrera,
                'created_at' => $fecha,
                'updated_at' => $fecha,
            ]);

            $description = "Se registró la carrera: $nombreCarrera con ID: $id";
            $this->auditoriaController->auditar(
                'CpuCarreraController',
                'agregarCarrera(Request $request)',
                '',
                json_encode(['id' => $id, 'nombre' => $nombreCarrera]),
                'INSERT',
                $description
            );
            return response()->json(['success' => true, 'message' => 'Carrera agregada correctamente', 'id' => $id]);
        } catch (\Exception $e) {
            // Registrar en log personalizado si está disponible
            \Log::error("Error al insertar carrera: " . $e->getMessage());

            $this->logController->saveLog('Nombre de Controlador: CpuCarreraController, Nombre de Funcion: agregarCarrera(Request $request)', 'Error al agregar carrera: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'No se pudo registrar la carrera',
                'mensaje' => $e->getMessage()
            ], 500);
        }
    }


    public function modificarCarrera(Request $request, $id)
    {
        try {
            $data = $request->all();
            $validator = Validator::make($request->all(), [
                'select-sede' => 'required|integer',
                'select-facultad' => 'required|integer',
                'txt-carrera' => 'required|string',
                'select-estado' => 'required|integer',
            ]);

            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()], 400);
            }

            // Actualizar la carrera en la tabla cpu_carrera
            DB::table('cpu_carrera')->where('id', $id)->update([
                'name' =>   $data['txt-carrera'],
                'id_estado' =>  $data['select-estado'],
                'created_at' => now(),
                'updated_at' => now(),
                'id_facultad' =>  $data['select-facultad'],
                'id_user' =>  $data['id_usuario']
            ]);

            $fecha = now();

            $descripcionAuditoria = 'Se Modifico la carrera: ' . $data['txt-carrera'] . ' el : ' . $fecha . ' con ID: ' . $id;
            $this->auditoriaController->auditar('cpu_carrera', 'modificarCarrera()', '', json_encode($data), 'UPDATE', $descripcionAuditoria);

            return response()->json(['success' => true, 'message' => 'Carrera modificada correctamente']);
        } catch (\Exception $e) {
            $this->logController->saveLog('Nombre de Controlador: CpuCarreraController, Nombre de Funcion: modificarCarrera(Request $request, $id)', 'Error al modificar carrera: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'No se pudo modificar la carrera',
                'mensaje' => $e->getMessage()
            ], 500);
        }
    }

    public function eliminarCarreraAnt(Request $request, $id)
    {
        try {
            $nombreCarrera = DB::table('cpu_carrera')->where('id', $id)->value('car_nombre');
            $ip = $request->ip();
            $fecha = now();

            DB::table('cpu_carrera')->where('id', $id)->delete();

            $descripcionAuditoria = 'Se eliminó la carrera: ' . $nombreCarrera . ' el : ' . $fecha . ' con ID: ' . $id;
            $this->auditoriaController->auditar('cpu_carrera', 'eliminarCarreraAnt(Request $request, $id)', '', json_encode(['id' => $id, 'nombre' => $nombreCarrera]), 'DELETE', $descripcionAuditoria);

            return response()->json(['success' => true, 'message' => 'Carrera eliminada correctamente']);
        } catch (\Exception $e) {
            $this->logController->saveLog('Nombre de Controlador: CpuCarreraController, Nombre de Funcion: eliminarCarreraAnt(Request $request, $id)', 'Error al eliminar carrera: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'No se pudo eliminar la carrera',
                'mensaje' => $e->getMessage()
            ], 500);
        }
    }

    public function eliminarCarrera(Request $request, $id)
    {
        try {
            $nombreCarrera = DB::table('cpu_carrera')->where('id', $id)->value('name');
            $data = DB::table('cpu_carrera')->where('id', $id)->get();

            $usuario = $request->user()->name;
            $ip = $request->ip();
            $nombreequipo = gethostbyaddr($ip);
            $fecha = now();
            DB::table('cpu_carrera')->where('id', $id)->delete();

            $descripcionAuditoria = 'Se elimino la carrera: ' . $nombreCarrera . ' el : ' . $fecha . ' con ID: ' . $id;
            $this->auditoriaController->auditar('cpu_carrera', 'eliminarCarrera()', '', $data, 'DELETE', $descripcionAuditoria);

            return response()->json(['success' => true, 'message' => 'Carrera eliminada correctamente']);
        } catch (\Exception $e) {
            $this->logController->saveLog('Nombre de Controlador: CpuCarreraController, Nombre de Funcion: eliminarCarrera(Request $request, $id)', 'Error al eliminar carrera: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'No se pudo eliminar la carrera',
                'mensaje' => $e->getMessage()
            ], 500);
        }
    }

    public function consultarCarreras()
    {
        try {
            $data = DB::table('cpu_carrera')->get();
            return response()->json($data);
        } catch (\Exception $e) {
            $this->logController->saveLog('Nombre de Controlador: CpuCarreraController, Nombre de Funcion: consultarCarreras()', $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar las carreras.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getCarreras()
    {
        try {
            $data = DB::table('cpu_carrera as c')
                ->join('cpu_estados as e', 'e.id', '=', 'c.id_estado')
                ->join('cpu_facultad as f', 'f.id', '=', 'c.id_facultad')
                ->join('cpu_sede as s', 'f.id_sede', '=', 's.id')
                ->select(
                    'c.id',
                    'c.name',
                    'c.id_estado',
                    'e.estado',
                    'c.created_at',
                    'c.updated_at',
                    'c.id_facultad',
                    'f.fac_nombre',
                    'f.id_sede',
                    's.nombre_sede'
                )
                ->orderByDesc('c.id')
                ->get();
            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar las carreras.',
                'error' => $e->getMessage(),
            ], 500);
            $this->logController->saveLog('Nombre de Controlador: CpuCarreraController, Nombre de Funcion: getCarreras()', $e->getMessage());
        }
    }
}
