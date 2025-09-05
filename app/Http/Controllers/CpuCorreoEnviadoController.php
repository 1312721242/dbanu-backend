<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CpuCorreoEnviadoController extends Controller
{


    public function enviarCorreoAtencionAreaSaludPaciente(Request $request)
    {
        try {
            // 1. Obtener email real del paciente (ya no fijo)
            $email_paciente = DB::table('cpu_datos_estudiantes')
                ->where('id_persona', $request['id_paciente'])
                ->value('email_institucional');
            if (!$email_paciente) {
                $email_paciente = DB::table('cpu_datos_empleados')
                    ->where('id_persona', $request['id_paciente'])
                    ->value('emailinstitucional');
            }
            if (!$email_paciente) {
                $email_paciente = DB::table('cpu_datos_usuarios_externos')
                    ->where('id_persona', $request['id_paciente'])
                    ->value('email');
            }
            if (!$email_paciente) {
                Log::warning('No se encontró email del paciente.', ['id_paciente' => $request['id_paciente']]);
                return response()->json(['error' => 'No se encontró email del paciente'], 400);
            }
            // Obtener los datos necesarios desde el array validado
            // $email_paciente = 'junior.zamora@uleam.edu.ec';

            $paciente = DB::table('cpu_personas')
                ->where('id', $request->input('id_paciente'))
                ->first();
            $fecha_de_atencion = $request->input('fecha_hora_atencion');
            $motivo_atencion = $request->input('motivo_atencion');
            $funcionario_atendio = DB::table('users')
                ->where('id', $request->input('id_funcionario'))
                ->value('name') ?? null;
            $area_atencion = DB::table('cpu_userrole')
                ->where('id_userrole', $request->input('id_area_atencion'))
                ->value('role');
            $funcionario_derivado = DB::table('users')
                ->where('id', $request->input('id_doctor_al_que_derivan'))
                ->value('name') ?? null;
            $area_derivada = DB::table('cpu_userrole')
                ->where('id_userrole', $request->input('id_area_derivada'))
                ->value('role');
            $fecha_de_derivacion = $request->input('fecha_para_atencion');
            $hora_de_derivacion = $request->input('hora_para_atencion');
            $motivo_derivacion = $request->input('motivo_derivacion');
            $id_atencion = base64_encode($request->input('id_atencion'));
            $url_encuesta_satisfaccion = "https://servicesdbanu.uleam.edu.ec/valoracion/valorar/" . $id_atencion . "/" . $paciente->id_clasificacion_tipo_usuario;
            $paciente = DB::table('cpu_personas')
                ->where('id', $request->input('id_paciente'))
                ->first();
            $fecha_de_atencion = $request->input('fecha_hora_atencion');
            $motivo_atencion = $request->input('motivo_atencion');
            // $funcionario_atendio = DB::table('users')
            //     ->where('id', $request->input('id_funcionario'))
            //     ->value('name') ?? null;
            // $area_atencion = DB::table('cpu_userrole')
            //     ->where('id_userrole', $request->input('id_area_atencion'))
            //     ->value('role');

            $funcionario = DB::table('users')
                ->select('name', 'usr_tipo')
                ->where('id', $request->input('id_funcionario'))
                ->first();

            $funcionario_atendio = $funcionario->name ?? null;
            $usr_tipo_funcionario = $funcionario->usr_tipo ?? null;

            $area_atencion = null;

            if ($request->filled('id_area_atencion')) {
                // Si viene id_area_atencion en el request, úsalo
                $area_atencion = DB::table('cpu_userrole')
                    ->where('id_userrole', $request->input('id_area_atencion'))
                    ->value('role');
            } elseif (!empty($usr_tipo_funcionario)) {
                // Si no viene id_area_atencion, usa usr_tipo del funcionario
                $area_atencion = DB::table('cpu_userrole')
                    ->where('id_userrole', $usr_tipo_funcionario)
                    ->value('role');
            }


            $funcionario_derivado = DB::table('users')
                ->where('id', $request->input('id_doctor_al_que_derivan'))
                ->value('name') ?? null;
            $area_derivada = DB::table('cpu_userrole')
                ->where('id_userrole', $request->input('id_area_derivada'))
                ->value('role');
            $fecha_de_derivacion = $request->input('fecha_para_atencion');
            $hora_de_derivacion = $request->input('hora_para_atencion');
            $motivo_derivacion = $request->input('motivo_derivacion');
            $id_atencion = base64_encode($request->input('id_atencion'));
            // url de la encuesta de satisfaccion
            $url_encuesta_satisfaccion = "https://servicesdbanu.uleam.edu.ec/valoracion/valorar/" . $id_atencion . "/" . $paciente->id_clasificacion_tipo_usuario;

            // Plan Nutricional si aplica
            $planNutricionalTexto = '';
            if (strtoupper($area_atencion) === "NUTRICIÓN") {
                $planNutricionalTexto = "<p><strong>📄 Plan Nutricional:</strong></p>";
                $planNutricionalTexto .= "<pre>{$request->input('plan_nutricional_texto')}</pre>";
            }

            // Asunto y cuerpo del correo
            $asunto = "Registro de atención en el área de $area_atencion";
            $cuerpo = "<p>Estimado(a) <strong>{$paciente->nombres}</strong>,</p>
            <p>Le informamos que el <strong>$fecha_de_atencion</strong>, la Dirección de Bienestar, Admisión y Nivelación Universitaria (DBANU) registró su atención en el área de <strong>$area_atencion</strong> con el/la funcionario(a) <strong>$funcionario_atendio</strong>. A continuación, los detalles de la atención:</p>
            <p><strong>📅 Fecha:</strong> $fecha_de_atencion<br>
            <strong>📌 Motivo:</strong> $motivo_atencion</p>
            <p>Le invitamos a compartir su opinión sobre la atención recibida completando la siguiente <a href='$url_encuesta_satisfaccion' target='_blank'><strong>🌐 Encuesta de satisfacción del servicio</strong></a>.</p>
            $planNutricionalTexto
            <p>Agradecemos su atención y quedamos atentos a cualquier inquietud.</p>
            <p>Atentamente,</p>
            <p><strong>Área de $area_atencion</strong><br>
            Dirección de Bienestar, Admisión y Nivelación Universitaria</p>";

            // 2. Obtener Token de acceso Microsoft (con credenciales de bienestar@uleam.edu.ec)
            $response = Http::withOptions(['verify' => false])->asForm()->post(
                'https://login.microsoftonline.com/31a17900-7589-4cfc-b11a-f4e83c27b8ed/oauth2/v2.0/token',
                [
                    'grant_type' => 'client_credentials',
                    'client_id' => '24e03a5e-0d5b-4c08-8382-bda010b7c3d4',
                    'client_secret' => 'QvD8Q~7K93W8JZUZjFyOvOy2FlS.pBmELA1SNb0S',
                    'scope' => 'https://graph.microsoft.com/.default'
                ]
            );
            $tokenResponse = $response->json();

            if (!isset($tokenResponse['access_token'])) {
                Log::error('Error al obtener token de acceso Microsoft', ['response' => $tokenResponse]);
                return response()->json(['error' => 'No se pudo obtener el token de acceso'], 500);
            }

            $accessToken = $tokenResponse['access_token'];
            $sender = "bienestar@uleam.edu.ec";
            $mailUrl = "https://graph.microsoft.com/v1.0/users/$sender/sendMail";
            $mailData = [
                "message" => [
                    "subject" => $asunto,
                    "body" => [
                        "contentType" => "html",
                        "content" => $cuerpo
                    ],
                    "toRecipients" => [
                        [
                            "emailAddress" => ["address" => $email_paciente]
                        ]
                    ]
                ]
            ];

            $sendResponse = Http::withOptions(['verify' => false])
                ->withToken($accessToken)
                ->post($mailUrl, $mailData);

            if ($sendResponse->successful()) {
                Log::info('Correo enviado correctamente con Microsoft Graph', ['email' => $email_paciente]);
                // (opcional) registra el correo enviado aquí si lo deseas
                return response()->json(['message' => 'Correo enviado correctamente'], 200);
            } else {
                Log::warning('Error al enviar correo con Microsoft Graph', [
                    'status' => $sendResponse->status(),
                    'response' => $sendResponse->body(),
                    'email' => $email_paciente
                ]);
                return response()->json([
                    'error' => 'Error al enviar correo',
                    'status' => $sendResponse->status(),
                    'response' => $sendResponse->body()
                ], 500);
            }
        } catch (\Exception $e) {
            Log::error('Excepción al enviar correo', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);
            return response()->json(['error' => 'Excepción al enviar correo', 'msg' => $e->getMessage()], 500);
        }
    }

    public function enviarCorreoDerivacionAreaSaludPaciente(Request $request)
    {
        try {
            // Buscar email real del paciente

            $email_paciente = DB::table('cpu_datos_estudiantes')
                ->where('id_persona', $request['id_paciente'])
                ->value('email_institucional');
            if (!$email_paciente) {
                $email_paciente = DB::table('cpu_datos_empleados')
                    ->where('id_persona', $request['id_paciente'])
                    ->value('emailinstitucional');
            }
            if (!$email_paciente) {
                $email_paciente = DB::table('cpu_datos_usuarios_externos')
                    ->where('id_persona', $request['id_paciente'])
                    ->value('email');
            }
            if (!$email_paciente) {
                Log::warning('No se encontró email del paciente.', ['id_paciente' => $request['id_paciente']]);
                return response()->json(['error' => 'No se encontró email del paciente'], 400);
            }

            $paciente = DB::table('cpu_personas')->where('id', $request->input('id_paciente'))->first();
            $fecha_de_atencion = $request->input('fecha_hora_atencion');
            $motivo_atencion = $request->input('motivo_atencion');
            $funcionario_atendio = DB::table('users')->where('id', $request->input('id_funcionario'))->value('name') ?? null;

            $email_funcionario = DB::table('users')->where('id', $request->input('id_funcionario'))->value('email');
            // $email_funcionario = 'junior.zamora@uleam.edu.ec';

            $area_atencion = DB::table('cpu_userrole')->where('id_userrole', $request->input('id_area_atencion'))->value('role');
            $funcionario_derivado = DB::table('users')->where('id', $request->input('id_doctor_al_que_derivan'))->value('name') ?? null;
            $email_funcionario_derivado = DB::table('users')->where('id', $request->input('id_doctor_al_que_derivan'))->value('email');
            $area_derivada = DB::table('cpu_userrole')->where('id_userrole', $request->input('id_area_derivada'))->value('role');
            $fecha_de_derivacion = $request->input('fecha_para_atencion');
            $hora_de_derivacion = $request->input('hora_para_atencion');
            $motivo_derivacion = $request->input('motivo_derivacion');
            $id_atencion = base64_encode($request->input('id_atencion'));
            $url_encuesta_satisfaccion = "https://servicesdbanu.uleam.edu.ec/valoracion/valorar/" . $id_atencion . "/" . $paciente->id_clasificacion_tipo_usuario;

            // Asunto
            $asunto = "Registro de agendamiento de cita en el área de $area_derivada";

            // Cuerpo según área derivada
            if ($area_derivada == "FISIOTERAPIA" && $paciente->id_clasificacion_tipo_usuario != 1) {
                $cuerpo = "<p>Estimado(a) <strong>$paciente->nombres</strong>,</p>
                        <p>Le informamos que el <strong>$fecha_de_atencion</strong>, la Dirección de Bienestar, Admisión y Nivelación Universitaria (DBANU) registró su derivación por motivo de <strong>$motivo_derivacion</strong> al área de <strong>$area_derivada</strong>. Su cita ha sido programada con el/la funcionario(a) <strong>$funcionario_derivado</strong>. A continuación, los detalles de su cita:</p>
                        <p><strong>📅 Fecha:</strong> $fecha_de_derivacion<br>
                        <strong>⏰ Hora:</strong> $hora_de_derivacion<br>
                        <strong>📍 Lugar:</strong> Universidad Laica Eloy Alfaro de Manabí<br>
                        <strong>📌 Dirección:</strong> Bienestar Universitario, Área de Fisioterapia</p>
                        <p>Le solicitamos presentarse <strong>15 minutos antes de la hora de la cita</strong> y acudir previamente al área de <strong>TRIAJE</strong>.</p>
                        <p><i><strong>Nota:</strong> Durante la primera sesión, el fisioterapeuta podrá solicitar otros implementos adicionales según sea necesario.</i></p>
                        <p>En caso de no poder asistir en la fecha y hora programadas, le pedimos que lo comunique oportunamente al correo <strong>$email_funcionario_derivado</strong>.</p>
                        <p>Agradecemos su atención y quedamos atentos a cualquier inquietud.</p>
                        <p>Atentamente,</p>
                        <p>$area_atencion<br>
                        Dirección de Bienestar, Admisión y Nivelación Universitaria</p>";
            } else if ($area_derivada == "TRABAJO SOCIAL") {
                $cuerpo = "<p>Estimado(a) <strong>$paciente->nombres</strong>,</p>
                       <p>Reciba un cordial saludo de parte del Área de Trabajo Social de la Dirección de Bienestar, Admisión y Nivelación Universitaria (Dbanu) de la Universidad Laica Eloy Alfaro de Manabí (ULEAM).</p>
                       <p>Le notificamos que, el <strong>$fecha_de_atencion</strong>, la Dbanu registró su derivación por motivo de <strong>$motivo_derivacion</strong> al área de <strong>$area_derivada</strong>.
                       La entrevista ha sido programada con el/la funcionario(a) <strong>$funcionario_derivado</strong> a continuación se detallan los datos de la cita:</p>
                       <p><strong>📅 Fecha:</strong> $fecha_de_derivacion<br>
                       <strong>⏰ Hora:</strong> $hora_de_derivacion<br>
                       <strong>📍 Lugar:</strong> Universidad Laica Eloy Alfaro de Manabí<br>
                       <strong>📌 Dirección:</strong> Bienestar Universitario, Área de Trabajo Social.</p>
                       <p>Le solicitamos presentarse <strong>15 minutos antes de la hora de la cita</strong>.</p>
                       <p>En caso de no poder asistir en la fecha y hora programadas, le pedimos que lo comunique oportunamente al correo <strong>$email_funcionario</strong>.</p>
                       <p>Agradecemos su atención y quedamos atentos a cualquier inquietud.</p>
                       <p>Atentamente,</p>
                       Secretaría<br>
                       Dirección de Bienestar, Admisión y Nivelación Universitaria</p>";
            } else {
                $cuerpo = "<p>Estimado(a) <strong>$paciente->nombres</strong>,</p>
                       <p>Le informamos que el <strong>$fecha_de_atencion</strong>, la Dirección de Bienestar, Admisión y Nivelación Universitaria (DBANU) registró su derivación por motivo de <strong>$motivo_derivacion</strong> al área de <strong>$area_derivada</strong>. Su cita ha sido programada con el/la funcionario(a) <strong>$funcionario_derivado</strong>. A continuación, los detalles de su cita:</p>
                       <p><strong>📅 Fecha:</strong> $fecha_de_derivacion<br>
                       <strong>⏰ Hora:</strong> $hora_de_derivacion<br>
                       <strong>📍 Lugar:</strong> Universidad Laica Eloy Alfaro de Manabí<br>
                       <strong>📌 Dirección:</strong> Área de $area_derivada</p>
                       <p>Le solicitamos presentarse <strong>15 minutos antes de la hora de la cita</strong> y acudir previamente al área de <strong>TRIAJE</strong>.</p>
                       <p>En caso de no poder asistir en la fecha y hora programadas, le pedimos que lo comunique oportunamente al correo <strong>$email_funcionario_derivado</strong>.</p>
                       <p>Agradecemos su atención y quedamos atentos a cualquier inquietud.</p>
                       <p>Atentamente,</p>
                       <p>$area_atencion<br>
                       Dirección de Bienestar, Admisión y Nivelación Universitaria</p>";
            }

            // 1. Obtener token de acceso de Microsoft
            $response = Http::withOptions(['verify' => false])->asForm()->post(
                'https://login.microsoftonline.com/31a17900-7589-4cfc-b11a-f4e83c27b8ed/oauth2/v2.0/token',
                [
                    'grant_type' => 'client_credentials',
                    'client_id' => '24e03a5e-0d5b-4c08-8382-bda010b7c3d4',
                    'client_secret' => 'QvD8Q~7K93W8JZUZjFyOvOy2FlS.pBmELA1SNb0S',
                    'scope' => 'https://graph.microsoft.com/.default'
                ]
            );
            $tokenResponse = $response->json();

            if (!isset($tokenResponse['access_token'])) {
                Log::error('Error al obtener token de acceso Microsoft', ['response' => $tokenResponse]);
                return response()->json(['error' => 'No se pudo obtener el token de acceso'], 500);
            }

            $accessToken = $tokenResponse['access_token'];
            $sender = "bienestar@uleam.edu.ec";
            $mailUrl = "https://graph.microsoft.com/v1.0/users/$sender/sendMail";
            $mailData = [
                "message" => [
                    "subject" => $asunto,
                    "body" => [
                        "contentType" => "html",
                        "content" => $cuerpo
                    ],
                    "toRecipients" => [
                        [
                            "emailAddress" => ["address" => $email_paciente]
                        ]
                    ]
                ]
            ];

            $sendResponse = Http::withOptions(['verify' => false])
                ->withToken($accessToken)
                ->post($mailUrl, $mailData);

            if ($sendResponse->successful()) {
                Log::info('Correo enviado correctamente con Microsoft Graph', ['email' => $email_paciente]);
                // (opcional) registrar correo enviado
                return response()->json(['message' => 'Correo enviado correctamente'], 200);
            } else {
                Log::warning('Error al enviar correo con Microsoft Graph', [
                    'status' => $sendResponse->status(),
                    'response' => $sendResponse->body(),
                    'email' => $email_paciente
                ]);
                return response()->json([
                    'error' => 'Error al enviar correo',
                    'status' => $sendResponse->status(),
                    'response' => $sendResponse->body()
                ], 500);
            }
        } catch (\Exception $e) {
            Log::error('Excepción al enviar correo', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);
            return response()->json(['error' => 'Excepción al enviar correo', 'msg' => $e->getMessage()], 500);
        }
    }

    public function enviarCorreoDerivacionAreaSaludFuncionario(Request $request)
    {
        try {
            // Buscar el email real del funcionario
            $email_funcionario = DB::table('users')
                ->where('id', $request->input('id_doctor_al_que_derivan'))
                ->value('email');
            if (!$email_funcionario) {
                Log::warning('No se encontró email del funcionario.', ['id_funcionario' => $request->input('id_funcionario')]);
                return response()->json(['error' => 'No se encontró email del funcionario'], 400);
            }
            // $email_funcionario = $request->input('id_doctor_al_que_derivan');

            Log::info('Enviando correo de derivación al funcionario', [
                'email_funcionario' => $email_funcionario,
                'id_funcionario' => $request->input('id_funcionario'),
                'id_paciente' => $request->input('id_paciente')
            ]);
            // $email_funcionario = 'oscari.briones@uleam.edu.ec';
            $nombres_funcionario = DB::table('cpu_personas')
                ->where('id', $request->input('id_funcionario'))
                ->value('nombres');
            $fecha_de_atencion = $request->input('fecha_hora_atencion');
            $motivo_atencion = $request->input('motivo_atencion');
            $funcionario_atendio = DB::table('users')
                ->where('id', $request->input('id_funcionario'))
                ->value('name') ?? null;
            $area_atencion = DB::table('cpu_userrole')
                ->where('id_userrole', $request->input('id_area_atencion'))
                ->value('role');
            $nombres_paciente = DB::table('cpu_personas')
                ->where('id', $request->input('id_paciente'))
                ->value('nombres');
            $area_derivada = DB::table('cpu_userrole')
                ->where('id_userrole', $request->input('id_area_derivada'))
                ->value('role');
            $fecha_de_derivacion = $request->input('fecha_para_atencion');
            $hora_de_derivacion = $request->input('hora_para_atencion');
            $motivo_derivacion = $request->input('motivo_derivacion');
            $id_atencion = base64_encode($request->input('id_atencion'));
            $url_encuesta_satisfaccion = "https://servicesdbanu.uleam.edu.ec/valoracion/valorar/" . $id_atencion;

            // Asunto y cuerpo del correo
            $asunto = "Registro de agendamiento de cita en el área de $area_derivada";
            $cuerpo = "<p>Estimado(a) funcionario(a) <strong>$nombres_funcionario</strong>; el <strong>$fecha_de_atencion</strong>, La Dirección de Bienestar, Admisión y Nivelación Universitaria, registró la derivación desde el área de <strong>$area_atencion</strong> por motivo de <strong>$motivo_derivacion</strong> con el/la ciudadano(a) <strong>$nombres_paciente</strong> para el día <strong>$fecha_de_derivacion</strong> a las <strong>$hora_de_derivacion</strong>. Por favor revise la cita en el <a href='https://dbanu.uleam.edu.ec/bienestar/'><strong>sistema</strong></a> en la sección de Derivaciones&gt;Atender.<br><br>Saludos cordiales.</p>";

            // 1. Obtener token de acceso de Microsoft
            $response = Http::withOptions(['verify' => false])->asForm()->post(
                'https://login.microsoftonline.com/31a17900-7589-4cfc-b11a-f4e83c27b8ed/oauth2/v2.0/token',
                [
                    'grant_type' => 'client_credentials',
                    'client_id' => '24e03a5e-0d5b-4c08-8382-bda010b7c3d4',
                    'client_secret' => 'QvD8Q~7K93W8JZUZjFyOvOy2FlS.pBmELA1SNb0S',
                    'scope' => 'https://graph.microsoft.com/.default'
                ]
            );
            $tokenResponse = $response->json();

            if (!isset($tokenResponse['access_token'])) {
                Log::error('Error al obtener token de acceso Microsoft', ['response' => $tokenResponse]);
                return response()->json(['error' => 'No se pudo obtener el token de acceso'], 500);
            }

            $accessToken = $tokenResponse['access_token'];
            $sender = "bienestar@uleam.edu.ec";
            $mailUrl = "https://graph.microsoft.com/v1.0/users/$sender/sendMail";
            $mailData = [
                "message" => [
                    "subject" => $asunto,
                    "body" => [
                        "contentType" => "html",
                        "content" => $cuerpo
                    ],
                    "toRecipients" => [
                        [
                            "emailAddress" => ["address" => $email_funcionario]
                        ]
                    ]
                ]
            ];

            $sendResponse = Http::withOptions(['verify' => false])
                ->withToken($accessToken)
                ->post($mailUrl, $mailData);

            if ($sendResponse->successful()) {
                Log::info('Correo enviado correctamente con Microsoft Graph', ['email' => $email_funcionario]);
                // (opcional) registrar correo enviado
                return response()->json(['message' => 'Correo enviado correctamente'], 200);
            } else {
                Log::warning('Error al enviar correo con Microsoft Graph', [
                    'status' => $sendResponse->status(),
                    'response' => $sendResponse->body(),
                    'email' => $email_funcionario
                ]);
                return response()->json([
                    'error' => 'Error al enviar correo',
                    'status' => $sendResponse->status(),
                    'response' => $sendResponse->body()
                ], 500);
            }
        } catch (\Exception $e) {
            Log::error('Excepción al enviar correo', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);
            return response()->json(['error' => 'Excepción al enviar correo', 'msg' => $e->getMessage()], 500);
        }
    }

    public function enviarCorreoAtencionPaciente(array $validatedData, $id_atencion, $derivado)
    {
        try {
            // Obtener correo del paciente
            $email_paciente = DB::table('cpu_datos_estudiantes')
                ->where('id_persona', $validatedData['id_persona'])
                ->value('email_institucional');

            if (!$email_paciente) {
                $email_paciente = DB::table('cpu_datos_empleados')
                    ->where('id_persona', $validatedData['id_persona'])
                    ->value('emailinstitucional');
            }

            if (!$email_paciente) {
                $email_paciente = DB::table('cpu_datos_usuarios_externos')
                    ->where('id_persona', $validatedData['id_persona'])
                    ->value('email');
            }

            $area_atencion = DB::table('cpu_userrole')
                ->where('id_userrole', $validatedData['id_area_atencion'])
                ->value('role');

            $fecha_para_atencion = $validatedData['fecha_hora_atencion'];
            $motivo_atencion = $validatedData['motivo_atencion'];
            $id_paciente = $validatedData['id_persona'];
            $id_funcionario_derivado = $validatedData['derivacion.id_doctor_al_que_derivan'] ?? null;
            $id_funcionario_atendio = $validatedData['id_funcionario'];
            $id_atencion_base64 = base64_encode($id_atencion);
            $url_encuesta_satisfaccion = "https://servicesdbanu.uleam.edu.ec/valoracion/valorar/" . $id_atencion_base64;

            // Construcción del mensaje
            $asunto = "Registro de atención en el área de $area_atencion";
            $cuerpo = "<p>Estimado(a) ciudadano(a); La Dirección de Bienestar, Admisión y Nivelación de la Universidad Laica Eloy Alfaro de Manabí, informa que se registró una atención por concepto de <strong>$motivo_atencion</strong> en el área de <strong>$area_atencion</strong> el día y hora <strong>$fecha_para_atencion</strong>. Por favor califique la atención en la siguiente <a href='$url_encuesta_satisfaccion'>Encuesta de satisfacción del servicio recibido</a>.<br><br>Saludos cordiales.</p>";

            // 1. Obtener token de acceso de Microsoft
            $tokenResponse = Http::withOptions(['verify' => false])->asForm()->post(
                'https://login.microsoftonline.com/31a17900-7589-4cfc-b11a-f4e83c27b8ed/oauth2/v2.0/token',
                [
                    'grant_type' => 'client_credentials',
                    'client_id' => '24e03a5e-0d5b-4c08-8382-bda010b7c3d4',
                    'client_secret' => 'QvD8Q~7K93W8JZUZjFyOvOy2FlS.pBmELA1SNb0S',
                    'scope' => 'https://graph.microsoft.com/.default'
                ]
            )->json();

            if (!isset($tokenResponse['access_token'])) {
                Log::error('Error al obtener token de Microsoft Graph', ['response' => $tokenResponse]);
                return response()->json(['error' => 'No se pudo obtener el token de acceso'], 500);
            }

            $accessToken = $tokenResponse['access_token'];

            // 2. Enviar correo vía Graph API
            $sender = 'bienestar@uleam.edu.ec';
            $mailUrl = "https://graph.microsoft.com/v1.0/users/$sender/sendMail";
            $mailData = [
                "message" => [
                    "subject" => $asunto,
                    "body" => [
                        "contentType" => "html",
                        "content" => $cuerpo
                    ],
                    "toRecipients" => [
                        [
                            "emailAddress" => ["address" => $email_paciente]
                        ]
                    ]
                ]
            ];

            $sendResponse = Http::withOptions(['verify' => false])
                ->withToken($accessToken)
                ->post($mailUrl, $mailData);

            if ($sendResponse->successful()) {
                Log::info('Correo enviado correctamente con Microsoft Graph', ['email' => $email_paciente]);

                // Registrar el correo en la base
                $this->registrarCorreoEnviado(
                    $email_paciente,
                    "",
                    $asunto,
                    $cuerpo,
                    $id_paciente,
                    $id_funcionario_derivado,
                    $id_funcionario_atendio
                );

                return response()->json(['message' => 'Correo enviado correctamente'], 200);
            } else {
                Log::warning('Error al enviar correo con Microsoft Graph', [
                    'status' => $sendResponse->status(),
                    'response' => $sendResponse->body()
                ]);
                return response()->json([
                    'error' => 'Error al enviar correo',
                    'status' => $sendResponse->status(),
                    'response' => $sendResponse->body()
                ], 500);
            }
        } catch (\Exception $e) {
            Log::error('Excepción al enviar correo de atención al paciente', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $validatedData
            ]);
            return response()->json(['error' => 'Excepción al enviar correo', 'msg' => $e->getMessage()], 500);
        }
    }

    public function enviarCorreoDerivacionPaciente(array $validatedData)
    {
        try {
            // Obtener email del paciente
            $email_paciente = DB::table('cpu_datos_estudiantes')
                ->where('id_persona', $validatedData['id_persona'])
                ->value('email_institucional');

            if (!$email_paciente) {
                $email_paciente = DB::table('cpu_datos_empleados')
                    ->where('id_persona', $validatedData['id_persona'])
                    ->value('emailinstitucional');
            }

            if (!$email_paciente) {
                $email_paciente = DB::table('cpu_datos_usuarios_externos')
                    ->where('id_persona', $validatedData['id_persona'])
                    ->value('email');
            }

            // Obtener email del funcionario al que se deriva (para CC)
            $email_funcionario_derivado = DB::table('users')
                ->where('id', $validatedData['derivacion.id_doctor_al_que_derivan'])
                ->value('email');

            $area_atencion = DB::table('cpu_userrole')
                ->where('id_userrole', $validatedData['derivacion.id_area'])
                ->value('role');

            $fecha_para_atencion = $validatedData['derivacion.fecha_para_atencion'];
            $hora_para_atencion = $validatedData['derivacion.hora_para_atencion'];

            $nombres_funcionario = DB::table('users')
                ->where('id', $validatedData['derivacion.id_doctor_al_que_derivan'])
                ->value('name');

            $id_paciente = $validatedData['id_persona'];
            $id_funcionario_derivado = $validatedData['derivacion.id_doctor_al_que_derivan'] ?? null;
            $id_funcionario_atendio = $validatedData['id_funcionario'];

            // Asunto y cuerpo del correo
            $asunto = "Agendamiento de cita en el área de $area_atencion";
            $cuerpo = "<p>Estimado(a) ciudadano(a); La Dirección de Bienestar, Admisión y Nivelación de la Universidad Laica Eloy Alfaro de Manabí, informa que se registró un agendamiento de cita para el área de <strong>$area_atencion</strong> con el funcionario/a <strong>$nombres_funcionario</strong> para el día <strong>$fecha_para_atencion</strong> a las <strong>$hora_para_atencion</strong>. Por favor estar presente 15 minutos antes de la hora de la cita.<br><br>Saludos cordiales.</p>";

            // 1. Obtener token de acceso de Microsoft
            $tokenResponse = Http::withOptions(['verify' => false])->asForm()->post(
                'https://login.microsoftonline.com/31a17900-7589-4cfc-b11a-f4e83c27b8ed/oauth2/v2.0/token',
                [
                    'grant_type' => 'client_credentials',
                    'client_id' => '24e03a5e-0d5b-4c08-8382-bda010b7c3d4',
                    'client_secret' => 'QvD8Q~7K93W8JZUZjFyOvOy2FlS.pBmELA1SNb0S',
                    'scope' => 'https://graph.microsoft.com/.default'
                ]
            )->json();

            if (!isset($tokenResponse['access_token'])) {
                Log::error('Error al obtener token de Microsoft Graph', ['response' => $tokenResponse]);
                return response()->json(['error' => 'No se pudo obtener el token de acceso'], 500);
            }

            $accessToken = $tokenResponse['access_token'];

            // 2. Enviar correo vía Graph API
            $sender = 'bienestar@uleam.edu.ec';
            $mailUrl = "https://graph.microsoft.com/v1.0/users/$sender/sendMail";

            $mailData = [
                "message" => [
                    "subject" => $asunto,
                    "body" => [
                        "contentType" => "html",
                        "content" => $cuerpo
                    ],
                    "toRecipients" => [
                        ["emailAddress" => ["address" => $email_paciente]]
                    ],
                    "ccRecipients" => $email_funcionario_derivado ? [
                        ["emailAddress" => ["address" => $email_funcionario_derivado]]
                    ] : []
                ]
            ];

            $sendResponse = Http::withOptions(['verify' => false])
                ->withToken($accessToken)
                ->post($mailUrl, $mailData);

            if ($sendResponse->successful()) {
                Log::info('✅ Correo de derivación enviado correctamente al paciente', ['email' => $email_paciente]);

                // Registrar en base de datos
                $this->registrarCorreoEnviado(
                    $email_paciente,
                    $email_funcionario_derivado,
                    $asunto,
                    $cuerpo,
                    $id_paciente,
                    $id_funcionario_derivado,
                    $id_funcionario_atendio
                );

                return response()->json(['message' => 'Correo enviado correctamente'], 200);
            } else {
                Log::warning('⚠️ Error al enviar correo con Microsoft Graph', [
                    'status' => $sendResponse->status(),
                    'response' => $sendResponse->body()
                ]);
                return response()->json([
                    'error' => 'Error al enviar correo',
                    'status' => $sendResponse->status(),
                    'response' => $sendResponse->body()
                ], 500);
            }
        } catch (\Exception $e) {
            Log::error('Excepción al enviar correo de derivación al paciente', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $validatedData
            ]);
            return response()->json(['error' => 'Excepción al enviar correo', 'msg' => $e->getMessage()], 500);
        }
    }

    //enviar correo no asistio paciente
    public function enviarCorreoNoAsistioPaciente(array $validatedData, $tipo)
    {
        // Obtener los datos necesarios desde el array validado
        // $email_paciente = $validatedData['email_paciente'];
        $email_paciente = 'p1311836587@dn.uleam.edu.ec';
        $area_atencion = $validatedData['area_atencion'];
        $fecha_para_atencion = $validatedData['fecha_para_atencion'];
        $hora_para_atencion = $validatedData['hora_para_atencion'];
        $nombres_funcionario = $validatedData['nombres_funcionario'];

        // Ajustar el asunto y el cuerpo del correo según el tipo
        if ($tipo === 'no_show') {
            $asunto = "Cita no asistida en el área de $area_atencion";
            $cuerpo = "<p>Estimado(a) ciudadano(a); La Dirección de Bienestar, Admisión y Nivelación Universitaria, informa que no asistió a su cita programada para el área de $area_atencion el día $fecha_para_atencion a las $hora_para_atencion. Si desea reagendar su cita, por favor acerquese al área de salud de la dirección. Saludos cordiales.</p>";
        } else {
            $asunto = "Reagendamiento de cita programada para el área de $area_atencion";
            $cuerpo = "<p>Estimado(a) ciudadano(a); La Dirección de Bienestar, Admisión y Nivelación Universitaria, registra el reagendamiento de una cita programada para el área de $area_atencion para la fecha $fecha_para_atencion a las $hora_para_atencion con el funcionario $nombres_funcionario, por favor presentarse 10 minutos antes, saludos cordiales.</p>";
        }

        $persona = [
            "destinatarios" => $email_paciente,
            "cc" => "",
            "cco" => "",
            "asunto" => $asunto,
            "cuerpo" => $cuerpo
        ];

        // Codificar los datos
        $datosCodificados = json_encode($persona);

        // URL de destino
        $url = "https://prod-44.westus.logic.azure.com:443/workflows/4046dc46113a4d8bb5da374ef1ee3e32/triggers/manual/paths/invoke?api-version=2016-06-01&sp=%2Ftriggers%2Fmanual%2Frun&sv=1.0&sig=lA40KwffEyLqEjVA4uyHaWAHblO77vk2jXYEkjUG08s";

        // Inicializar cURL
        $ch = curl_init($url);

        // Configurar opciones de cURL
        curl_setopt_array($ch, array(
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $datosCodificados,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($datosCodificados),
                'Personalizado: ¡Hola mundo!',
            ),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false, // Deshabilitar verificación SSL (solo para pruebas)
            CURLOPT_SSL_VERIFYHOST => false, // Deshabilitar verificación del host SSL (solo para pruebas)
        ));

        // Realizar la solicitud cURL
        $resultado = curl_exec($ch);
        $codigoRespuesta = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Procesar la respuesta
        if ($codigoRespuesta === 200) {
            $respuestaDecodificada = json_decode($resultado);
            // Registrar el correo enviado en la base de datos
            $this->registrarCorreoEnviado(
                $email_paciente,
                "",
                $asunto,
                $cuerpo,
                $validatedData['id_paciente'],
                $validatedData['id_funcionario_derivado'],
                $validatedData['id_funcionario_atendio']
            );
        } else {
            // Manejar errores
            return response()->json(['error' => "Error consultando. Código de respuesta: $codigoRespuesta"], $codigoRespuesta);
        }

        // Devolver una respuesta
        return response()->json(['message' => 'Solicitud enviada correctamente'], 200);
    }

    public function enviarCorreoNoAsistioFuncionario(array $validatedData, $tipo)
    {
        try {
            $funcionario_email = $validatedData['funcionario_email'];
            $area_atencion = $validatedData['area_atencion'];
            $fecha_para_atencion = $validatedData['fecha_para_atencion'];
            $hora_para_atencion = $validatedData['hora_para_atencion'];
            $nombres_paciente = $validatedData['nombres_paciente'];

            // Asunto y cuerpo según el tipo
            if ($tipo === 'no_show') {
                $asunto = "Paciente no asistió a la cita en el área de $area_atencion";
                $cuerpo = "<p>Estimado(a) funcionario(a); La Dirección de Bienestar, Admisión y Nivelación Universitaria, informa que el ciudadano(a) <strong>$nombres_paciente</strong> no asistió a su cita programada para el área de <strong>$area_atencion</strong> el día <strong>$fecha_para_atencion</strong> a las <strong>$hora_para_atencion</strong>. Saludos cordiales.</p>";
            } else {
                $asunto = "Reagendamiento de cita programada para el área de $area_atencion";
                $cuerpo = "<p>Estimado(a) funcionario(a); La Dirección de Bienestar, Admisión y Nivelación Universitaria, registra el reagendamiento de una cita para el área de <strong>$area_atencion</strong> para la fecha <strong>$fecha_para_atencion</strong> a las <strong>$hora_para_atencion</strong> con el ciudadano(a) <strong>$nombres_paciente</strong>. Saludos cordiales.</p>";
            }

            // 1. Obtener token de acceso
            $tokenResponse = Http::withOptions(['verify' => false])->asForm()->post(
                'https://login.microsoftonline.com/31a17900-7589-4cfc-b11a-f4e83c27b8ed/oauth2/v2.0/token',
                [
                    'grant_type' => 'client_credentials',
                    'client_id' => '24e03a5e-0d5b-4c08-8382-bda010b7c3d4',
                    'client_secret' => 'QvD8Q~7K93W8JZUZjFyOvOy2FlS.pBmELA1SNb0S',
                    'scope' => 'https://graph.microsoft.com/.default'
                ]
            )->json();

            if (!isset($tokenResponse['access_token'])) {
                Log::error('Error al obtener token de Microsoft Graph', ['response' => $tokenResponse]);
                return response()->json(['error' => 'No se pudo obtener el token de acceso'], 500);
            }

            $accessToken = $tokenResponse['access_token'];

            // 2. Enviar correo vía Microsoft Graph API
            $sender = 'bienestar@uleam.edu.ec';
            $mailUrl = "https://graph.microsoft.com/v1.0/users/$sender/sendMail";
            $mailData = [
                "message" => [
                    "subject" => $asunto,
                    "body" => [
                        "contentType" => "html",
                        "content" => $cuerpo
                    ],
                    "toRecipients" => [
                        [
                            "emailAddress" => ["address" => $funcionario_email]
                        ]
                    ]
                ]
            ];

            $sendResponse = Http::withOptions(['verify' => false])
                ->withToken($accessToken)
                ->post($mailUrl, $mailData);

            if ($sendResponse->successful()) {
                Log::info('Correo enviado correctamente al funcionario', ['email' => $funcionario_email]);

                // Registrar en base de datos
                $this->registrarCorreoEnviado(
                    $funcionario_email,
                    "",
                    $asunto,
                    $cuerpo,
                    $validatedData['id_paciente'],
                    $validatedData['id_funcionario_derivado'],
                    $validatedData['id_funcionario_atendio']
                );

                return response()->json(['message' => 'Correo enviado correctamente'], 200);
            } else {
                Log::warning('Error al enviar correo con Microsoft Graph', [
                    'status' => $sendResponse->status(),
                    'response' => $sendResponse->body()
                ]);
                return response()->json([
                    'error' => 'Error al enviar correo',
                    'status' => $sendResponse->status(),
                    'response' => $sendResponse->body()
                ], 500);
            }
        } catch (\Exception $e) {
            Log::error('Excepción al enviar correo no asistió/reagendamiento', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $validatedData
            ]);
            return response()->json(['error' => 'Excepción al enviar correo', 'msg' => $e->getMessage()], 500);
        }
    }

    public function enviarCorreoPaciente(array $validatedData, $tipo)
    {
        Log::info('Datos correo al paciente', [
            'data' => $validatedData,
            'tipo' => $tipo
        ]);

        try {
            // Buscar el email del paciente
            $email_paciente = DB::table('cpu_datos_estudiantes')
                ->where('id_persona', $validatedData['id_paciente'])
                ->value('email_institucional');

            if (!$email_paciente) {
                $email_paciente = DB::table('cpu_datos_empleados')
                    ->where('id_persona', $validatedData['id_paciente'])
                    ->value('emailinstitucional');
            }

            if (!$email_paciente) {
                $email_paciente = DB::table('cpu_datos_usuarios_externos')
                    ->where('id_persona', $validatedData['id_paciente'])
                    ->value('email');
            }

            $area_atencion = $validatedData['area_atencion'];
            $fecha_para_atencion = $validatedData['fecha_para_atencion'];
            $hora_para_atencion = $validatedData['hora_para_atencion'];
            $nombres_funcionario = $validatedData['nombres_funcionario'];

            // Construir asunto y cuerpo
            if ($tipo === 'no_show') {
                $asunto = "Cita no asistida en el área de $area_atencion";
                $cuerpo = "<p>Estimado(a) ciudadano(a); La Dirección de Bienestar, Admisión y Nivelación Universitaria, informa que no asistió a su cita programada para el área de <strong>$area_atencion</strong> el día <strong>$fecha_para_atencion</strong> a las <strong>$hora_para_atencion</strong>. Si desea reagendar su cita, por favor acérquese al área de salud de la dirección. <br><br>Saludos cordiales.</p>";
            } else {
                $asunto = "Reagendamiento de cita programada para el área de $area_atencion";
                $cuerpo = "<p>Estimado(a) ciudadano(a); La Dirección de Bienestar, Admisión y Nivelación Universitaria, registra el reagendamiento de una cita programada para el área de <strong>$area_atencion</strong> para la fecha <strong>$fecha_para_atencion</strong> a las <strong>$hora_para_atencion</strong> con el funcionario <strong>$nombres_funcionario</strong>. Por favor presentarse 15 minutos antes. <br><br>Saludos cordiales.</p>";
            }

            // 1. Obtener token de Microsoft Graph
            $tokenResponse = Http::withOptions(['verify' => false])->asForm()->post(
                'https://login.microsoftonline.com/31a17900-7589-4cfc-b11a-f4e83c27b8ed/oauth2/v2.0/token',
                [
                    'grant_type' => 'client_credentials',
                    'client_id' => '24e03a5e-0d5b-4c08-8382-bda010b7c3d4',
                    'client_secret' => 'QvD8Q~7K93W8JZUZjFyOvOy2FlS.pBmELA1SNb0S',
                    'scope' => 'https://graph.microsoft.com/.default'
                ]
            )->json();

            if (!isset($tokenResponse['access_token'])) {
                Log::error('Error al obtener token de Microsoft Graph', ['response' => $tokenResponse]);
                return;
            }

            $accessToken = $tokenResponse['access_token'];

            // 2. Enviar el correo vía Graph API
            $sender = 'bienestar@uleam.edu.ec';
            $mailUrl = "https://graph.microsoft.com/v1.0/users/$sender/sendMail";
            $mailData = [
                "message" => [
                    "subject" => $asunto,
                    "body" => [
                        "contentType" => "html",
                        "content" => $cuerpo
                    ],
                    "toRecipients" => [
                        [
                            "emailAddress" => ["address" => $email_paciente]
                        ]
                    ]
                ]
            ];

            $sendResponse = Http::withOptions(['verify' => false])
                ->withToken($accessToken)
                ->post($mailUrl, $mailData);

            if ($sendResponse->successful()) {
                Log::info('Correo enviado al paciente', ['email' => $email_paciente]);

                $this->registrarCorreoEnviado(
                    $email_paciente,
                    "",
                    $asunto,
                    $cuerpo,
                    $validatedData['id_paciente'],
                    $validatedData['id_funcionario_derivado'] ?? null,
                    $validatedData['id_funcionario_atendio'] ?? null
                );
            } else {
                Log::error('Error al enviar correo al paciente', [
                    'status' => $sendResponse->status(),
                    'response' => $sendResponse->body()
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Excepción al enviar correo al paciente', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $validatedData
            ]);
        }
    }


    public function enviarCorreoFuncionario(array $validatedData, $tipo)
    {
        try {
            $funcionario_email = $validatedData['funcionario_email'];
            $area_atencion = $validatedData['area_atencion'];
            $fecha_para_atencion = $validatedData['fecha_para_atencion'];
            $hora_para_atencion = $validatedData['hora_para_atencion'];
            $nombres_paciente = $validatedData['nombres_paciente'];

            Log::info('Datos correo al funcionario reagendamiento', [
                'email' => $funcionario_email,
                'area_atencion' => $area_atencion,
                'fecha_para_atencion' => $fecha_para_atencion,
                'hora_para_atencion' => $hora_para_atencion,
                'nombres_paciente' => $nombres_paciente,
                'tipo' => $tipo
            ]);

            if ($tipo === 'no_show') {
                $asunto = "Paciente no asistió a la cita en el área de $area_atencion";
                $cuerpo = "<p>Estimado(a) funcionario(a); La Dirección de Bienestar, Admisión y Nivelación Universitaria informa que el ciudadano(a) <strong>$nombres_paciente</strong> no asistió a su cita programada para el área de <strong>$area_atencion</strong> el día <strong>$fecha_para_atencion</strong> a las <strong>$hora_para_atencion</strong>. Saludos cordiales.</p>";
            } else {
                $asunto = "Reagendamiento de cita programada para el área de $area_atencion";
                $cuerpo = "<p>Estimado(a) funcionario(a); La Dirección de Bienestar, Admisión y Nivelación Universitaria registra el reagendamiento de una cita para el área de <strong>$area_atencion</strong> para la fecha <strong>$fecha_para_atencion</strong> a las <strong>$hora_para_atencion</strong> con el ciudadano(a) <strong>$nombres_paciente</strong>. Saludos cordiales.</p>";
            }

            $tokenResponse = Http::withOptions(['verify' => false])->asForm()->post(
                'https://login.microsoftonline.com/31a17900-7589-4cfc-b11a-f4e83c27b8ed/oauth2/v2.0/token',
                [
                    'grant_type' => 'client_credentials',
                    'client_id' => '24e03a5e-0d5b-4c08-8382-bda010b7c3d4',
                    'client_secret' => 'QvD8Q~7K93W8JZUZjFyOvOy2FlS.pBmELA1SNb0S',
                    'scope' => 'https://graph.microsoft.com/.default'
                ]
            )->json();

            if (!isset($tokenResponse['access_token'])) {
                Log::error('Error al obtener token de Microsoft Graph', ['response' => $tokenResponse]);
                return;
            }

            $accessToken = $tokenResponse['access_token'];
            $sender = 'bienestar@uleam.edu.ec';
            $mailUrl = "https://graph.microsoft.com/v1.0/users/$sender/sendMail";

            $mailData = [
                "message" => [
                    "subject" => $asunto,
                    "body" => [
                        "contentType" => "html",
                        "content" => $cuerpo
                    ],
                    "toRecipients" => [
                        [
                            "emailAddress" => ["address" => $funcionario_email]
                        ]
                    ]
                ]
            ];

            $sendResponse = Http::withOptions(['verify' => false])
                ->withToken($accessToken)
                ->post($mailUrl, $mailData);

            if ($sendResponse->successful()) {
                Log::info('Correo enviado al funcionario', ['email' => $funcionario_email]);

                $this->registrarCorreoEnviado(
                    $funcionario_email,
                    "",
                    $asunto,
                    $cuerpo,
                    $validatedData['id_paciente'] ?? null,
                    $validatedData['id_funcionario_derivado'] ?? null,
                    $validatedData['id_funcionario_atendio'] ?? null
                );
            } else {
                Log::error('Error al enviar correo al funcionario', [
                    'status' => $sendResponse->status(),
                    'response' => $sendResponse->body()
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Excepción al enviar correo al funcionario', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $validatedData
            ]);
        }
    }


    public function registrarCorreoEnviado($destinatarios, $conCopia, $asunto, $cuerpo, $idPaciente, $idFuncionarioDerivado, $idFuncionarioAtendio)
    {
        // Crear un nuevo registro en la tabla cpu_correos_enviados
        $correoEnviado = new \App\Models\CpuCorreoEnviado();
        $correoEnviado->destinatarios = json_encode($destinatarios);
        $correoEnviado->con_copia = json_encode($conCopia);
        $correoEnviado->asunto = $asunto;
        $correoEnviado->cuerpo = $cuerpo;
        $correoEnviado->id_paciente = $idPaciente;
        $correoEnviado->id_funcionario_derivado = $idFuncionarioDerivado;
        $correoEnviado->id_funcionario_atendio = $idFuncionarioAtendio;
        $correoEnviado->created_at = now();
        $correoEnviado->updated_at = now();
        $correoEnviado->save();

        return response()->json(['message' => 'Correo registrado correctamente'], 200);
    }

    public function enviarCorreoTrabajoSocialPaciente(Request $request)
    {
        try {
            // ------------------ 1) Resolver email del paciente ------------------
            $idPaciente = $request->input('id_paciente');

            $email_paciente = DB::table('cpu_datos_estudiantes')
                ->where('id_persona', $idPaciente)
                ->value('email_institucional');

            if (!$email_paciente) {
                $email_paciente = DB::table('cpu_datos_empleados')
                    ->where('id_persona', $idPaciente)
                    ->value('emailinstitucional');
            }
            if (!$email_paciente) {
                $email_paciente = DB::table('cpu_datos_usuarios_externos')
                    ->where('id_persona', $idPaciente)
                    ->value('email');
            }
            if (!$email_paciente) {
                Log::warning('No se encontró email del paciente (Trabajo Social).', ['id_paciente' => $idPaciente]);
                return response()->json(['error' => 'No se encontró email del paciente'], 400);
            }

            // ------------------ 2) Datos base ------------------
            $paciente = DB::table('cpu_personas')->where('id', $idPaciente)->first();

            $fecha_atencion    = $request->input('fecha_hora_atencion');     // ej. "2025-09-04 11:30:00"
            $motivo_atencion   = $request->input('motivo_atencion');         // ej. "EXONERACIÓN DE PAGO"
            $detalle_atencion  = $request->input('detalle_atencion');        // ej. "SOCIOECONÓMICO"
            $idAtencionPlain   = $request->input('id_atencion');             // numérico
            $id_atencion       = $idAtencionPlain ? base64_encode($idAtencionPlain) : null;

            $idFuncionario       = $request->input('id_funcionario');
            $funcionario_atendio = DB::table('users')->where('id', $idFuncionario)->value('name') ?? null;
            $email_funcionario   = DB::table('users')->where('id', $idFuncionario)->value('email') ?? null;

            // Área que registra
            $area_atencion = DB::table('cpu_userrole')
                ->where('id_userrole', $request->input('id_area_atencion'))
                ->value('role') ?? 'TRABAJO SOCIAL';

            // ------------------ 3) ¿Hay derivación? ------------------
            $deriva = (bool) $request->input('id_doctor_al_que_derivan'); // si viene id del funcionario derivado
            $funcionario_derivado = null;
            $email_funcionario_derivado = null;
            $area_derivada = null;
            $fecha_derivacion = null;
            $hora_derivacion = null;
            $motivo_derivacion = null;

            if ($deriva) {
                $idDoctorDerivan = $request->input('id_doctor_al_que_derivan');
                $funcionario_derivado = DB::table('users')->where('id', $idDoctorDerivan)->value('name') ?? '';
                $email_funcionario_derivado = DB::table('users')->where('id', $idDoctorDerivan)->value('email') ?? '';
                $area_derivada = DB::table('cpu_userrole')
                    ->where('id_userrole', $request->input('id_area_derivada'))
                    ->value('role') ?? '—';
                $fecha_derivacion = $request->input('fecha_para_atencion');
                $hora_derivacion  = $request->input('hora_para_atencion');
                $motivo_derivacion = $request->input('motivo_derivacion');
            }

            // ------------------ 4) Encuesta de satisfacción ------------------
            $url_encuesta_satisfaccion = null;
            if ($id_atencion && isset($paciente->id_clasificacion_tipo_usuario)) {
                $url_encuesta_satisfaccion = "https://servicesdbanu.uleam.edu.ec/valoracion/valorar/" . $id_atencion . "/" . $paciente->id_clasificacion_tipo_usuario;
            }

            // ------------------ 5) Asunto y cuerpo ------------------
            if ($deriva) {
                // Derivación desde Trabajo Social
                $asunto = "Registro de agendamiento de cita en el área de $area_derivada";

                $cuerpo = "<p>Estimado(a) <strong>{$paciente->nombres}</strong>,</p>
                <p>Reciba un cordial saludo de parte del Área de Trabajo Social de la Dirección de Bienestar, Admisión y Nivelación Universitaria (Dbanu) de la Universidad Laica Eloy Alfaro de Manabí (ULEAM).</p>
                <p>Le notificamos que, el <strong>{$fecha_atencion}</strong>, la Dbanu registró su derivación por motivo de <strong>{$motivo_derivacion}</strong> al área de <strong>{$area_derivada}</strong>.
                La entrevista ha sido programada con el/la funcionario(a) <strong>{$funcionario_derivado}</strong>. A continuación se detallan los datos de la cita:</p>
                <p><strong>📅 Fecha:</strong> {$fecha_derivacion}<br>
                <strong>⏰ Hora:</strong> {$hora_derivacion}<br>
                <strong>📍 Lugar:</strong> Universidad Laica Eloy Alfaro de Manabí<br>
                <strong>📌 Dirección:</strong> Bienestar Universitario, Área de {$area_derivada}.</p>
                <p>Le solicitamos presentarse <strong>15 minutos antes de la hora de la cita</strong>.</p>
                <p>En caso de no poder asistir en la fecha y hora programadas, le pedimos que lo comunique oportunamente al correo <strong>{$email_funcionario}</strong>.</p>";
            } else {
                // Solo atención registrada en Trabajo Social (sin derivación)
                $asunto = "Constancia de atención — $area_atencion";

                $cuerpo = "<p>Estimado(a) <strong>{$paciente->nombres}</strong>,</p>
                <p>Reciba un cordial saludo de parte de la Dirección de Bienestar, Admisión y Nivelación Universitaria (Dbanu) de la Universidad Laica Eloy Alfaro de Manabí (ULEAM).</p>
                <p>Le informamos que el <strong>{$fecha_atencion}</strong> se registró su atención en <strong>{$area_atencion}</strong>, por el motivo <strong>{$motivo_atencion}</strong>" . ($detalle_atencion ? " ({$detalle_atencion})" : "") . ".</p>
                <p><strong>👤 Profesional que lo atendió:</strong> {$funcionario_atendio}</p>";
            }

            // Pie común + encuesta si existe
            $cuerpo .= "<p>Agradecemos su atención y quedamos atentos a cualquier inquietud.</p>";
            if ($url_encuesta_satisfaccion) {
                $cuerpo .= "<p>Por favor, ayúdenos con su retroalimentación:
                        <a href=\"{$url_encuesta_satisfaccion}\">Valorar mi atención</a></p>";
            }
            $cuerpo .= "<p>Atentamente,</p>
                    <p>Secretaría<br>
                    Dirección de Bienestar, Admisión y Nivelación Universitaria</p>";

            // ------------------ 6) Microsoft Graph (credenciales directas) ------------------
            $tokenResponse = Http::withOptions(['verify' => false])->asForm()->post(
                'https://login.microsoftonline.com/31a17900-7589-4cfc-b11a-f4e83c27b8ed/oauth2/v2.0/token',
                [
                    'grant_type'    => 'client_credentials',
                    'client_id'     => '24e03a5e-0d5b-4c08-8382-bda010b7c3d4',
                    'client_secret' => 'QvD8Q~7K93W8JZUZjFyOvOy2FlS.pBmELA1SNb0S',
                    'scope'         => 'https://graph.microsoft.com/.default'
                ]
            )->json();

            if (!isset($tokenResponse['access_token'])) {
                Log::error('Error al obtener token MS Graph (Trabajo Social)', ['response' => $tokenResponse]);
                return response()->json(['error' => 'No se pudo obtener el token de acceso'], 500);
            }

            $accessToken = $tokenResponse['access_token'];
            $sender = "bienestar@uleam.edu.ec";
            $mailUrl = "https://graph.microsoft.com/v1.0/users/{$sender}/sendMail";

            // CC opcional: funcionario que atendió y funcionario derivado (si aplica)
            $cc = [];
            if ($email_funcionario) {
                $cc[] = ["emailAddress" => ["address" => $email_funcionario]];
            }
            if ($deriva && $email_funcionario_derivado) {
                $cc[] = ["emailAddress" => ["address" => $email_funcionario_derivado]];
            }

            $mailData = [
                "message" => [
                    "subject" => $asunto,
                    "body" => [
                        "contentType" => "html",
                        "content" => $cuerpo
                    ],
                    "toRecipients" => [
                        ["emailAddress" => ["address" => $email_paciente]]
                    ],
                    // Descomenta si quieres enviar copia:
                    // "ccRecipients" => $cc,
                ]
            ];

            $sendResponse = Http::withOptions(['verify' => false])
                ->withToken($accessToken)
                ->post($mailUrl, $mailData);

            if ($sendResponse->successful()) {
                Log::info('Correo Trabajo Social enviado (MS Graph)', [
                    'email' => $email_paciente,
                    'derivacion' => $deriva
                ]);
                return response()->json(['message' => 'Correo enviado correctamente'], 200);
            } else {
                Log::warning('Error al enviar correo Trabajo Social (MS Graph)', [
                    'status' => $sendResponse->status(),
                    'response' => $sendResponse->body(),
                    'email' => $email_paciente
                ]);
                return response()->json([
                    'error' => 'Error al enviar correo',
                    'status' => $sendResponse->status(),
                    'response' => $sendResponse->body()
                ], 500);
            }
        } catch (\Exception $e) {
            Log::error('Excepción al enviar correo Trabajo Social', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);
            return response()->json(['error' => 'Excepción al enviar correo', 'msg' => $e->getMessage()], 500);
        }
    }
}
