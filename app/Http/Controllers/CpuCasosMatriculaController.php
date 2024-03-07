<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CpuCasosMatricula;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\CpuNotificacionMatricula;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

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
    // Configurar la zona horaria a Ecuador
    date_default_timezone_set('America/Guayaquil');
    $fechaHoraActual = date('Y-m-d H:i:s');
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

    // Obtener el caso de matrícula
    $casoMatricula = CpuCasosMatricula::find($idCaso);

    if (!$casoMatricula) {
        return response()->json(['error' => 'Caso de matrícula no encontrado'], 404);
    }

    // Asignar el ID de legalización a la notificación
    $notificacion->id_legalizacion = $casoMatricula->id_legalizacion_matricula;

    // Verificar si todos los estados son igual a 10
    if (isset($documentos['estado_cedula']) && $documentos['estado_cedula'] == 10 &&
        isset($documentos['estado_titulo']) && $documentos['estado_titulo'] == 10 &&
        isset($documentos['estado_cupo']) && $documentos['estado_cupo'] == 10) {
        $notificacion->titulo = 'Proceso de legalización de matrícula satisfactorio';
        $notificacion->mensaje = 'Proceso de legalización de matrícula satisfactorio, esté pendiente a su correo electrónico para próximas indicaciones sobre el proceso de Nivelación. ¡Bienvenido/a a la Uleam!';
    } else {
        // Verificar si algún estado es igual a 11
        if (isset($documentos['estado_cedula']) && $documentos['estado_cedula'] == 11 ||
            isset($documentos['estado_titulo']) && $documentos['estado_titulo'] == 11 ||
            isset($documentos['estado_cupo']) && $documentos['estado_cupo'] == 11) {
            $notificacion->titulo = 'Corregir documentos';
        } else {
            $notificacion->titulo = 'Legalización de matrícula satisfactoria';
        }
    }

    $notificacion->save();



    // Actualizar estados de cedula, titulo y cupo si se proporcionan
    if (isset($documentos['estado_cedula'])) {
        $casoMatricula->legalizacionMatricula->estado_identificacion = $documentos['estado_cedula'];
    }
    if (isset($documentos['estado_titulo'])) {
        $casoMatricula->legalizacionMatricula->estado_titulo = $documentos['estado_titulo'];
    }
    if (isset($documentos['estado_cupo'])) {
        $casoMatricula->legalizacionMatricula->estado_cupo = $documentos['estado_cupo'];
    }
    $casoMatricula->legalizacionMatricula->save();

    // Enviar correo electrónico según el estado de los campos
    $asunto = '';
    $cuerpo = '';

    if ($casoMatricula->legalizacionMatricula->estado_identificacion == 10 &&
        $casoMatricula->legalizacionMatricula->estado_titulo == 10 &&
        $casoMatricula->legalizacionMatricula->estado_cupo == 10) {
        $asunto = 'Legalización de matrícula correcta';
        $cuerpo = "La legalización de matrícula para el/la ciudadano/a {$casoMatricula->legalizacionMatricula->nombres} {$casoMatricula->legalizacionMatricula->apellidos} con cédula {$casoMatricula->legalizacionMatricula->cedula} en la sede {$casoMatricula->legalizacionMatricula->sede->nombre_sede} y carrera {$casoMatricula->legalizacionMatricula->carrera->name} ha sido realizada correctamente el día {$fechaHoraActual}.";
    } elseif ($casoMatricula->legalizacionMatricula->estado_identificacion == 11 ||
              $casoMatricula->legalizacionMatricula->estado_titulo == 11 ||
              $casoMatricula->legalizacionMatricula->estado_cupo == 11) {
                $asunto = 'Corregir Archivo';

                if ($casoMatricula->legalizacionMatricula->estado_identificacion == 11) {
                    $asunto .= ' Identificación';
                }
                if ($casoMatricula->legalizacionMatricula->estado_titulo == 11) {
                    $asunto .= ' Título o Acta de Grado';
                }
                if ($casoMatricula->legalizacionMatricula->estado_cupo == 11) {
                    $asunto .= ' Cupo';
                }
                
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
        $cuerpo = "La legalización de matrícula para el/la ciudadano/a {$casoMatricula->legalizacionMatricula->nombres} {$casoMatricula->legalizacionMatricula->apellidos} con número de identificación {$casoMatricula->legalizacionMatricula->cedula} en la sede {$casoMatricula->legalizacionMatricula->sede->nombre_sede} y carrera {$casoMatricula->legalizacionMatricula->carrera->name} presenta las siguientes novedades: {$observacion} Por favor, corrija los documentos necesarios.";
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

    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $datosCodificados);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($datosCodificados)
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $resultado = curl_exec($ch);
    $codigoRespuesta = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    echo "Respuesta: $resultado, Código: $codigoRespuesta";

    return response()->json(['message' => 'Documentos revisados y notificación enviada'], 200);
}



}
