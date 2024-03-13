<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CpuCasosMatricula;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\CpuNotificacionMatricula;
use App\Models\CpuLegalizacionMatricula;
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
        ->where('cpu_casos_matricula.id_estado', '!=', 14) 
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
            'cpu_legalizacion_matricula.estado_cupo',
            'cpu_legalizacion_matricula.id_notificacion'
        )
        ->get();

        foreach ($casosMatricula as $caso) {
            $caso->copia_identificacion = url('Files/' . $caso->copia_identificacion);
            $caso->copia_titulo = url('Files/' . $caso->copia_titulo);
            $caso->copia_aceptacion_cupo = url('Files/' . $caso->copia_aceptacion_cupo);
        
            // Obtener el mensaje de la notificación asociada al caso de matrícula si existe
            $notificacionMatricula = CpuNotificacionMatricula::find($caso->id_notificacion);
            $caso->mensaje_notificacion = $notificacionMatricula ? $notificacionMatricula->mensaje : null;

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
    $notificacion->save();

    // Obtener el ID de la notificación creada
    $idNotificacion = $notificacion->id;

    // Actualizar el campo id_notificacion en la tabla cpu_legalizacion_matricula
    $legalizacionMatricula = CpuLegalizacionMatricula::find($casoMatricula->id_legalizacion_matricula);
    if ($legalizacionMatricula) {
        $legalizacionMatricula->id_notificacion = $idNotificacion;
        $legalizacionMatricula->save();
    } else {
        return response()->json(['error' => 'Legalización de matrícula no encontrada'], 404);
    }

    // Verificar si todos los estados son igual a 10
    if (isset($documentos['estado_cedula']) && $documentos['estado_cedula'] == 10 &&
        isset($documentos['estado_titulo']) && $documentos['estado_titulo'] == 10 &&
        isset($documentos['estado_cupo']) && $documentos['estado_cupo'] == 10) {
        $notificacion->titulo = 'Proceso de legalización de matrícula satisfactorio';
        $notificacion->mensaje = 'Proceso de legalización de matrícula satisfactorio, esté pendiente a su correo electrónico para próximas indicaciones sobre el proceso de Nivelación. ¡Bienvenido/a a la Uleam!';
        // Actualizar el estado a 14 en la tabla cpu_casos_matricula
        $casoMatricula->id_estado = 14;
    } else {
        // Verificar si algún estado es igual a 11
        if (isset($documentos['estado_cedula']) && $documentos['estado_cedula'] == 11 ||
            isset($documentos['estado_titulo']) && $documentos['estado_titulo'] == 11 ||
            isset($documentos['estado_cupo']) && $documentos['estado_cupo'] == 11) {
                // Actualizar el estado a 14 en la tabla cpu_casos_matricula
        $casoMatricula->id_estado = 13;
            $notificacion->titulo = 'Corregir documentos';
        } else {
            $notificacion->titulo = 'Legalización de matrícula satisfactoria';
        }
    }
    $notificacion->save();
    $casoMatricula->save();

    // Resto del código...
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
        // Actualizar el estado a 14 en la tabla cpu_casos_matricula
        $casoMatricula->id_estado = 14;
        $casoMatricula->save();
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
            $observacion .= "El documento de identificación presenta inconvenientes. ";
        }
        if ($casoMatricula->legalizacionMatricula->estado_titulo == 11) {
            $observacion .= "El documento de título o acta de grado presenta inconvenientes. ";
        }
        if ($casoMatricula->legalizacionMatricula->estado_cupo == 11) {
            $observacion .= "El documento de aceptación de cupo presenta inconvenientes. ";
        }
        $cuerpo = "La legalización de matrícula para el/la ciudadano/a {$casoMatricula->legalizacionMatricula->nombres} {$casoMatricula->legalizacionMatricula->apellidos} con número de identificación {$casoMatricula->legalizacionMatricula->cedula} en la sede {$casoMatricula->legalizacionMatricula->sede->nombre_sede} y carrera {$casoMatricula->legalizacionMatricula->carrera->name} presenta las siguientes novedades: {$observacion} Por favor, ingrese a la aplicación de matricula de nivelación de la Uleam y corrija los documentos necesarios a brevedad.";
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

    return response()->json(['message' => 'Documentos revisados y notificación enviada', 'id_notificacion' => $idNotificacion], 200);
}


//obtener todos los registros de matricula de ese proceso:

public function getMatriculaCases($id_usuario, $id_periodo)
{
    // Buscar el id_secretaria por medio del id_usuario
    $secretaria = DB::table('cpu_secretaria_matricula')
        ->where('id_usuario', $id_usuario)
        ->first();

    if (!$secretaria) {
        return response()->json(['error' => 'Secretaria no encontrada'], 404);
    }

    // Obtener el id_secretaria
    $idSecretaria = $secretaria->id;

    $cases = DB::table('cpu_legalizacion_matricula as lm')
        ->select([
            'cm.id', 'lm.id_periodo', 'lm.id_registro_nacional', 'lm.id_postulacion', 'lm.ciudad_campus',
            'lm.id_sede', 'lm.id_facultad', 'lm.id_carrera', 'lm.email', 'lm.cedula', 'lm.apellidos',
            'lm.nombres', 'lm.genero', 'lm.etnia', 'lm.discapacidad', 'lm.segmento_persona',
            'lm.nota_postulacion', 'lm.fecha_nacimiento', 'lm.nacionalidad', 'lm.provincia_reside',
            'lm.canton_reside', 'lm.parroquia_reside', 'lm.instancia_postulacion',
            'lm.instancia_de_asignacion', 'lm.gratuidad', 'lm.observacion_gratuidad',
            'lm.copia_identificacion', 'lm.copia_titulo_acta_grado', 'lm.copia_aceptacion_cupo',
            'lm.id_notificacion', 'lm.listo_para_revision', 'lm.legalizo_matricula', 'lm.created_at',
            'lm.updated_at', DB::raw('CASE 
                WHEN lm.estado_identificacion = 10 
                AND lm.estado_titulo = 10 
                AND lm.estado_cupo = 10 THEN \'Legalizado\'
                WHEN lm.estado_identificacion = 11 
                OR lm.estado_titulo = 11 
                OR lm.estado_cupo = 11 THEN \'En Corrección\'
                WHEN lm.estado_identificacion IS NULL 
                AND lm.estado_titulo IS NULL 
                AND lm.estado_cupo IS NULL THEN \'No subió documentos\'
                WHEN lm.estado_identificacion = 12 
                AND lm.estado_titulo = 12 
                AND lm.estado_cupo = 12 THEN \'Documentos cargados\'
                ELSE \'En proceso\'
            END AS estado_matricula'),
            DB::raw('(SELECT nombre FROM cpu_secretaria_matricula WHERE id = cm.id_secretaria) AS secretaria_nombre'),
            'cp.nombre AS nombre_periodo' // Agregar el nombre del periodo
        ])
        ->join('cpu_casos_matricula as cm', 'lm.id', '=', 'cm.id_legalizacion_matricula')
        ->join('cpu_periodo as cp', 'lm.id_periodo', '=', 'cp.id') // Unir con la tabla cpu_periodo
        ->where('lm.id_periodo', '=', $id_periodo)
        ->where('cm.id_secretaria', '=', $idSecretaria)
        ->get();

    return collect($cases);
}

//todos los registros de legalizacion_matricula, legalizados y no legalizados
public function getAllMatriculaCases($id_periodo)
{
    $cases = DB::table('cpu_legalizacion_matricula as lm')
                ->select([
                    'lm.id', 'lm.id_periodo', 'lm.id_registro_nacional', 'lm.id_postulacion', 'lm.ciudad_campus',
                    'lm.id_sede', 'sede.nombre_sede as nombre_sede',
                    'lm.id_facultad', 'facultad.fac_nombre as nombre_facultad',
                    'lm.id_carrera', 'carrera.name as nombre_carrera',
                    'lm.email', 'lm.cedula', 'lm.apellidos', 'lm.nombres', 'lm.genero', 'lm.etnia',
                    'lm.discapacidad', 'lm.segmento_persona', 'lm.nota_postulacion', 'lm.fecha_nacimiento',
                    'lm.nacionalidad', 'lm.provincia_reside', 'lm.canton_reside', 'lm.parroquia_reside',
                    'lm.instancia_postulacion', 'lm.instancia_de_asignacion', 'lm.gratuidad',
                    'lm.observacion_gratuidad', 'lm.copia_identificacion', 'lm.copia_titulo_acta_grado',
                    'lm.copia_aceptacion_cupo', 'lm.id_notificacion', 'lm.listo_para_revision',
                    'lm.legalizo_matricula', 'lm.created_at', 'lm.updated_at',
                    DB::raw('CASE 
                        WHEN lm.estado_identificacion = 10 
                        AND lm.estado_titulo = 10 
                        AND lm.estado_cupo = 10 THEN \'Legalizado\'
                        WHEN lm.estado_identificacion = 11 
                        OR lm.estado_titulo = 11 
                        OR lm.estado_cupo = 11 THEN \'En Corrección\'
                        WHEN lm.estado_identificacion IS NULL 
                        AND lm.estado_titulo IS NULL 
                        AND lm.estado_cupo IS NULL THEN \'No subió documentos\'
                        WHEN lm.estado_identificacion = 12 
                        AND lm.estado_titulo = 12 
                        AND lm.estado_cupo = 12 THEN \'Documentos cargados\'
                        ELSE \'En proceso\'
                    END AS estado_matricula'),
                      DB::raw('(SELECT nombre FROM cpu_secretaria_matricula WHERE id = cm.id_secretaria) AS secretaria_nombre')
                  ])
                ->leftJoin('cpu_casos_matricula as cm', 'lm.id', '=', 'cm.id_legalizacion_matricula')
                ->leftJoin('cpu_sede as sede', 'lm.id_sede', '=', 'sede.id')
                ->leftJoin('cpu_facultad as facultad', 'lm.id_facultad', '=', 'facultad.id')
                ->leftJoin('cpu_carrera as carrera', 'lm.id_carrera', '=', 'carrera.id')
                ->where('lm.id_periodo', '=', $id_periodo)
                ->orderBy('sede.nombre_sede')
                ->orderBy('carrera.name')
                ->orderBy('lm.apellidos')
                ->orderBy('lm.nombres')
                ->orderByRaw('CASE WHEN cm.id_legalizacion_matricula IS NOT NULL THEN 0 ELSE 1 END')
                ->get();

    return collect($cases);
}




}
