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

public function revisionDocumentos(Request $request, $idCaso)
{
    // Obtener el caso de matrícula
    $casoMatricula = CpuCasosMatricula::find($idCaso);

    if (!$casoMatricula) {
        return response()->json(['error' => 'Caso de matrícula no encontrado'], 404);
    }

    // Revisar si se han enviado documentos y guardarlos en la tabla cpu_notificacion_matricula
    $documentos = $request->all();
    $mensaje = isset($documentos['observacion']) ? $documentos['observacion'] : 'Documentos revisados sin observaciones';
    $notificacion = new CpuNotificacionMatricula();
    $notificacion->mensaje = $mensaje;
    $notificacion->save();

    // Enviar correo electrónico según el estado de los campos
    $asunto = '';
    $cuerpo = '';

    if ($casoMatricula->legalizacionMatricula->estado_identificacion == 10 &&
        $casoMatricula->legalizacionMatricula->estado_titulo == 10 &&
        $casoMatricula->legalizacionMatricula->estado_cupo == 10) {
        $asunto = 'Legalización de matrícula correcta';
        $cuerpo = "La legalización de matrícula para el ciudadano {$casoMatricula->legalizacionMatricula->nombres} {$casoMatricula->legalizacionMatricula->apellidos} con cédula {$casoMatricula->legalizacionMatricula->cedula} en la sede {$casoMatricula->legalizacionMatricula->sede->nombre_sede} y carrera {$casoMatricula->legalizacionMatricula->carrera->name} ha sido realizada correctamente el día {$casoMatricula->legalizacionMatricula->created_at}.";
    } elseif ($casoMatricula->legalizacionMatricula->estado_identificacion == 11 ||
              $casoMatricula->legalizacionMatricula->estado_titulo == 11 ||
              $casoMatricula->legalizacionMatricula->estado_cupo == 11) {
        $asunto = 'Novedades en el proceso de legalización de matrícula';
        $observacion = '';
        if ($casoMatricula->legalizacionMatricula->estado_identificacion == 11) {
            $observacion .= "El documento de identificación está pendiente de corrección. ";
        }
        if ($casoMatricula->legalizacionMatricula->estado_titulo == 11) {
            $observacion .= "El documento de título está pendiente de corrección. ";
        }
        if ($casoMatricula->legalizacionMatricula->estado_cupo == 11) {
            $observacion .= "El documento de aceptación de cupo está pendiente de corrección. ";
        }
        $cuerpo = "La legalización de matrícula para el ciudadano {$casoMatricula->legalizacionMatricula->nombres} {$casoMatricula->legalizacionMatricula->apellidos} con cédula {$casoMatricula->legalizacionMatricula->cedula} en la sede {$casoMatricula->legalizacionMatricula->sede->nombre_sede} y carrera {$casoMatricula->legalizacionMatricula->carrera->name} presenta las siguientes novedades: {$observacion} Por favor, corrija los documentos necesarios.";
    }

    // Obtener el email del usuario
    $usuario = DB::table('cpu_legalizacion_matricula')
        ->where('id', $casoMatricula->id_legalizacion_matricula)
        ->first();

    if (!$usuario) {
        return response()->json(['error' => 'Usuario no encontrado'], 404);
    }

    $persona = [
        "destinatarios" => $usuario->email,
        "cc" => "",
        "cco" => "",
        "asunto" => $asunto,
        "cuerpo" => $cuerpo
    ];

    $datosCodificados = json_encode($persona);

    $url = "https://prod-44.westus.logic.azure.com:443/workflows/4046dc46113a4d8bb5da374ef1ee3e32/triggers/manual/paths/invoke?api-version=2016-06-01&sp=%2Ftriggers%2Fmanual%2Frun&sv=1.0&sig=lA40KwffEyLqEjVA4uyHaWAHblO77vk2jXYEkjUG08s";
    $ch = curl_init($url);

    curl_setopt_array($ch, array(
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => $datosCodificados,
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($datosCodificados),
            'Personalizado: ¡Hola mundo!',
        ),
        CURLOPT_RETURNTRANSFER => true,
    ));

    $resultado = curl_exec($ch);
    $codigoRespuesta = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($codigoRespuesta === 200) {
        $respuestaDecodificada = json_decode($resultado);
        $array[0] = 1;
    } else {
        echo "Error consultando. Código de respuesta: $codigoRespuesta";
    }
    curl_close($ch);

    return response()->json(['message' => 'Documentos revisados y notificación enviada'], 200);
}


}
