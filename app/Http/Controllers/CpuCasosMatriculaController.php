<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CpuCasosMatricula;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CpuCasosMatriculaController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }
    public function index($idUsuario, $idPeriodo)
{
    // Buscar el id_secretaria por medio del id_usuario
    $secretaria = DB::table('cpu_secretaria_matricula')
        ->where('id_usuario', $idUsuario)
        ->first();

    if (!$secretaria) {
        return response()->json(['error' => 'Secretaria no encontrada'], 404);
    }

    // Obtener el id_secretaria
    $idSecretaria = $secretaria->id;

    // Consulta para obtener los casos de matricula asignados a la secretaria en el periodo dado
    $casosMatricula = DB::table('cpu_casos_matricula')
    ->join('cpu_legalizacion_matricula', 'cpu_casos_matricula.id_legalizacion_matricula', '=', 'cpu_legalizacion_matricula.id')
    ->join('cpu_carrera', 'cpu_legalizacion_matricula.id_carrera', '=', 'cpu_carrera.id')
    ->join('cpu_sede', 'cpu_legalizacion_matricula.id_sede', '=', 'cpu_sede.id')
    ->where('cpu_casos_matricula.id_secretaria', $idSecretaria)
    ->where('cpu_legalizacion_matricula.id_periodo', $idPeriodo)
    ->select(
        'cpu_casos_matricula.id as id_caso',
        'cpu_legalizacion_matricula.id as id_legalizacion',
        'cpu_legalizacion_matricula.nombres',
        'cpu_legalizacion_matricula.apellidos',
        'cpu_legalizacion_matricula.genero',
        'cpu_legalizacion_matricula.cedula',
        'cpu_legalizacion_matricula.etnia',
        'cpu_legalizacion_matricula.discapacidad',
        'cpu_legalizacion_matricula.id_sede as sede_id',
        'cpu_sede.nombre_sede as sede',
        'cpu_legalizacion_matricula.id_carrera as carrera_id',
        'cpu_carrera.name as carrera',
        'cpu_legalizacion_matricula.copia_identificacion',
        'cpu_legalizacion_matricula.estado_identificacion',
        'cpu_legalizacion_matricula.copia_titulo_acta_grado as copia_titulo',
        'cpu_legalizacion_matricula.estado_titulo',
        'cpu_legalizacion_matricula.copia_aceptacion_cupo',
        'cpu_legalizacion_matricula.estado_cupo'
    )
    ->get();

    foreach ($casosMatricula as $caso) {
        $caso->copia_identificacion = url('Files/' . $caso->copia_identificacion);
        $caso->copia_titulo = url('Files/' . $caso->copia_titulo);
        $caso->copia_aceptacion_cupo = url('Files/' . $caso->copia_aceptacion_cupo);
    }
    return response()->json($casosMatricula);

}

}
