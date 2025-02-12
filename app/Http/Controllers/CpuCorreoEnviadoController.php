<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CpuCorreoEnviadoController extends Controller
{


    // enviar correo atencion paciente
    public function enviarCorreoAtencionAdmisionSalud(Request $request)
    {
        // Obtener los datos necesarios desde el array validado
        $email_paciente = 'junior.zamora@uleam.edu.ec';
        $nombres_paciente = DB::table('cpu_personas')
        ->where('id', $request->input('id_paciente'))
        ->value('nombres');
        $fecha_de_atencion = $request->input('fecha_hora_atencion');
        $funcionario_derivado = DB::table('users')
            ->where('id', $request->input('id_doctor_al_que_derivan'))
            ->value('name') ?? null;
        $area_derivada = DB::table('cpu_userrole')
            ->where('id_userrole', $request->input('id_area_derivada'))
            ->value('role');
        $fecha_de_derivacion = $request->input('fecha_para_atencion');
        $hora_de_derivacion = $request->input('hora_para_atencion');

        // Ajustar el asunto y el cuerpo del correo según el tipo
        $asunto = "Registro de atención en el área de Admisión de Salud";
        $cuerpo = "<p>Estimado(a) ciudadano(a) $nombres_paciente; El $fecha_de_atencion, La Dirección de Bienestar, Admisión y Nivelación Universitaria, le informa que se registró el agendamiento en el área de $area_derivada con el funcionario $funcionario_derivado para el día $fecha_de_derivacion a las $hora_de_derivacion. Por favor asistir 15 minutos antes de la hora de la cita, acerquese al área de TRIAJE antes de asistir al área de $area_derivada.<br><br>Saludos cordiales.</p>";

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
            return response()->json(['message' => 'Correo enviado correctamente', 'respuesta' => $respuestaDecodificada], 200);

        } else {
            // Manejar errores
            return response()->json(['error' => "Error consultando. Código de respuesta: $codigoRespuesta"], $codigoRespuesta);
        }

        // Devolver una respuesta
        return response()->json(['message' => 'Solicitud enviada correctamente'], 200);
    }

    // enviar correo atencion paciente
    public function enviarCorreoAtencionAreaSaludPaciente(Request $request)
    {
        // Obtener los datos necesarios desde el array validado
        $email_paciente = 'junior.zamora@uleam.edu.ec';
        $nombres_paciente = DB::table('cpu_personas')
            ->where('id', $request->input('id_paciente'))
            ->value('nombres');
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
        $id_clasificacion_usuario = base64_encode($request->input('id_clasificacion_usuario'));
        // url de la encuesta de satisfaccion
        // $url_encuesta_satisfaccion = "https://servicesdbanu.uleam.edu.ec/valoracion/valorar/" . $id_atencion . "/" . $id_clasificacion_usuario;
        $url_encuesta_satisfaccion = "http://127.0.0.1:9000/valoracion/valorar/" . $id_atencion . "/" . $id_clasificacion_usuario;

        // Ajustar el asunto y el cuerpo del correo según el tipo
        $asunto = "Registro de atención en el área de $area_atencion";
        $cuerpo = "<p>Estimado(a) ciudadano(a) $nombres_paciente; El $fecha_de_atencion, La Dirección de Bienestar, Admisión y Nivelación Universitaria, registró la atención por motivo de $motivo_atencion en el área de $area_atencion con el funcionario $funcionario_atendio a las $fecha_de_atencion. Por favor califique la atención en la siguiente <a href='$url_encuesta_satisfaccion'>Encuesta de satisfacción del servicio recibido</a>.<br><br>Saludos cordiales.</p>";

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
            return response()->json(['message' => 'Correo enviado correctamente', 'respuesta' => $respuestaDecodificada], 200);

        } else {
            // Manejar errores
            return response()->json(['error' => "Error consultando. Código de respuesta: $codigoRespuesta"], $codigoRespuesta);
        }

        // Devolver una respuesta
        return response()->json(['message' => 'Solicitud enviada correctamente'], 200);
    }

    // enviar correo derivacion paciente
    public function enviarCorreoDerivacionAreaSaludPaciente(Request $request)
    {
        // Obtener los datos necesarios desde el array validado
        $email_paciente = 'junior.zamora@uleam.edu.ec';
        $nombres_paciente = DB::table('cpu_personas')
            ->where('id', $request->input('id_paciente'))
            ->value('nombres');
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
        // url de la encuesta de satisfaccion
        $url_encuesta_satisfaccion = "https://servicesdbanu.uleam.edu.ec/valoracion/valorar/" . $id_atencion;

        // Ajustar el asunto y el cuerpo del correo según el tipo
        $asunto = "Registro de agendamiento de cita en el área de $area_derivada";
        $cuerpo = "<p>Estimado(a) ciudadano(a) $nombres_paciente; El $fecha_de_atencion, La Dirección de Bienestar, Admisión y Nivelación Universitaria, registró la derivación por motivo de $motivo_derivacion en el área de $area_derivada con el funcionario $funcionario_derivado para el día $fecha_de_derivacion a las $hora_de_derivacion. Por favor asistir 15 minutos antes de la hora de la cita, acerquese al área de TRIAJE antes de asistir al área de $area_derivada.<br><br>Saludos cordiales.</p>";

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
            return response()->json(['message' => 'Correo enviado correctamente', 'respuesta' => $respuestaDecodificada], 200);

        } else {
            // Manejar errores
            return response()->json(['error' => "Error consultando. Código de respuesta: $codigoRespuesta"], $codigoRespuesta);
        }

        // Devolver una respuesta
        return response()->json(['message' => 'Solicitud enviada correctamente'], 200);
    }

    // enviar correo derivacion funcionario
    public function enviarCorreoDerivacionAreaSaludFuncionario(Request $request)
    {
        // Obtener los datos necesarios desde el array validado
        $email_funcionario = 'junior.zamora@uleam.edu.ec';
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
        // url de la encuesta de satisfaccion
        $url_encuesta_satisfaccion = "https://servicesdbanu.uleam.edu.ec/valoracion/valorar/" . $id_atencion;

        // Ajustar el asunto y el cuerpo del correo según el tipo
        $asunto = "Registro de agendamiento de cita en el área de $area_derivada";
        $cuerpo = "<p>Estimado(a) funcionario(a) $nombres_funcionario; El $fecha_de_atencion, La Dirección de Bienestar, Admisión y Nivelación Universitaria, registró la derivación desde el área de $area_atencion por motivo de $motivo_derivacion con el paciente $nombres_paciente para el día $fecha_de_derivacion a las $hora_de_derivacion. Por favor revise la cita en el sistema en la sección de Derivaciones>Atender.<br><br>Saludos cordiales.</p>";

        $persona = [
            "destinatarios" => $email_funcionario,
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
            return response()->json(['message' => 'Correo enviado correctamente', 'respuesta' => $respuestaDecodificada], 200);

        } else {
            // Manejar errores
            return response()->json(['error' => "Error consultando. Código de respuesta: $codigoRespuesta"], $codigoRespuesta);
        }

        // Devolver una respuesta
        return response()->json(['message' => 'Solicitud enviada correctamente'], 200);
    }

    // enviar correo atencion paciente
    public function enviarCorreoAtencionPaciente(array $validatedData, $id_atencion, $derivado)
    {
        // Obtener los datos necesarios desde el array validado
        // $email_paciente = $validatedData['email_paciente'];
        $email_paciente = 'junior.zamora@uleam.edu.ec';
        $area_atencion = $validatedData['id_area_atencion'];

        $area_atencion = DB::table('cpu_userrole')
            ->where('id_userrole', $validatedData['id_area_atencion'])
            ->value('role');
        $fecha_para_atencion = $validatedData['fecha_hora_atencion'];
        // $hora_para_atencion = $validatedData['hora_para_atencion'];
        // $nombres_funcionario = $validatedData['nombre_funcionario'];
        $motivo_atencion = $validatedData['motivo_atencion'];
        $id_paciente = $validatedData['id_persona'];

        $id_funcionario_derivado = $validatedData['derivacion.id_doctor_al_que_derivan'] ?? null;

        $id_funcionario_atendio = $validatedData['id_funcionario'];
        // $id_atencion = $validatedData['id_atencion'];
        // Convertir el id_atencion a base 64
        $id_atencion_base64 = base64_encode($id_atencion);

        // variable con el enlace para encuesta de satisfaccion
        $url_encuesta_satisfaccion = "https://servicesdbanu.uleam.edu.ec/valoracion/valorar/" . $id_atencion_base64;
        // Ajustar el asunto y el cuerpo del correo según el tipo

        $asunto = "Registro de atención en el area de $area_atencion";
        $cuerpo = "<p>Estimado(a) ciudadano(a); La Dirección de Bienestar, Admisión y Nivelación de la Universidad Laica Eloy Alfaro de Manabí, informa que se registró una atención por concepto de $motivo_atencion el área de $area_atencion el día y hora $fecha_para_atencion. Por favor califique la atención en la siguiente <a href='$url_encuesta_satisfaccion'>Encuesta de satisfacción del servicio recibido</a>.<br><br>Saludos cordiales.</p>";


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
                $id_paciente,
                $id_funcionario_derivado,
                $id_funcionario_atendio
            );

            // if ($derivado) {
            //     $this->enviarCorreoDerivacionPaciente($validatedData);
            // }

        } else {
            // Manejar errores
            return response()->json(['error' => "Error consultando. Código de respuesta: $codigoRespuesta"], $codigoRespuesta);
        }

        // Devolver una respuesta
        return response()->json(['message' => 'Solicitud enviada correctamente'], 200);
    }

    // enviar correo DERIVACION paciente
    public function enviarCorreoDerivacionPaciente(array $validatedData)
    {
        // Obtener los datos necesarios desde el array validado
        // $email_paciente = $validatedData['email_paciente'];
        $email_paciente = 'junior.zamora@uleam.edu.ec';
        // $email_funcionario_derivado = $validatedData['derivacion.id_doctor_al_que_derivan'];
        $email_funcionario_derivado = 'junior.zamora@uleam.edu.ec';
        // $area_atencion = $validatedData['id_area_atencion'];

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
        // $id_atencion = $validatedData['id_atencion'];

        // variable con el enlace para encuesta de satisfaccion

        $asunto = "Agendamiento de cita en el área de $area_atencion";
        $cuerpo = "<p>Estimado(a) ciudadano(a); La Dirección de Bienestar, Admisión y Nivelación de la Universidad Laica Eloy Alfaro de Manabí, informa que se registró un agendamiento de cita para el área de $area_atencion con el funcionario/a $nombres_funcionario para el día $fecha_para_atencion a las $hora_para_atencion. Por favor estar presente 15 minutos antes de la hora de la cita.<br><br>Saludos cordiales.</p>";


        $persona = [
            "destinatarios" => $email_paciente,
            "cc" => $email_funcionario_derivado,
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
                $id_paciente,
                $id_funcionario_derivado,
                $id_funcionario_atendio
            );
        } else {
            // Manejar errores
            return response()->json(['error' => "Error consultando. Código de respuesta: $codigoRespuesta"], $codigoRespuesta);
        }

        // Devolver una respuesta
        return response()->json(['message' => 'Solicitud enviada correctamente'], 200);
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
        // Obtener los datos necesarios desde el array validado
        // $funcionario_email = $validatedData['funcionario_email'];
        $funcionario_email = 'p1311836587@dn.uleam.edu.ec';
        $area_atencion = $validatedData['area_atencion'];
        $fecha_para_atencion = $validatedData['fecha_para_atencion'];
        $hora_para_atencion = $validatedData['hora_para_atencion'];
        $nombres_paciente = $validatedData['nombres_paciente'];

        // Ajustar el asunto y el cuerpo del correo según el tipo
        if ($tipo === 'no_show') {
            $asunto = "Paciente no asistió a la cita en el área de $area_atencion";
            $cuerpo = "<p>Estimado(a) funcionario(a); La Dirección de Bienestar, Admisión y Nivelación Universitaria, informa que el ciudadano(a) $nombres_paciente no asistió a su cita programada para el área de $area_atencion el día $fecha_para_atencion a las $hora_para_atencion. Saludos cordiales.</p>";
        } else {
            $asunto = "Reagendamiento de cita programada para el área de $area_atencion";
            $cuerpo = "<p>Estimado(a) funcionario(a); La Dirección de Bienestar, Admisión y Nivelación Universitaria, registra el reagendamiento de una cita para el área de $area_atencion para la fecha $fecha_para_atencion a las $hora_para_atencion con el ciudadano(a) $nombres_paciente, saludos cordiales.</p>";
        }

        $persona = [
            "destinatarios" => $funcionario_email,
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
                $funcionario_email,
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
}
