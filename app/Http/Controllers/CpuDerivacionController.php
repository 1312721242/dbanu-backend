<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CpuDerivacion;
use App\Models\CpuTurno;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CpuDerivacionController extends Controller
{
    public function index()
    {
        $derivaciones = CpuDerivacion::all();
        return response()->json($derivaciones);
    }

    public function show($id)
    {
        $derivacion = CpuDerivacion::find($id);

        if (!$derivacion) {
            return response()->json(['error' => 'Derivación no encontrada'], 404);
        }

        return response()->json($derivacion);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'ate_id' => 'required|integer|exists:cpu_atenciones,id',
            'id_doctor_al_que_derivan' => 'required|integer|exists:users,id',
            'id_paciente' => 'required|integer|exists:cpu_personas,id',
            'motivo_derivacion' => 'required|string',
            'detalle_derivacion' => 'required|string',
            'id_area' => 'required|integer',
            'fecha_para_atencion' => 'required|date',
            'hora_para_atencion' => 'required|date_format:H:i:s',
            'id_estado_derivacion' => 'integer|exists:cpu_estados,id',
            'id_turno_asignado' => 'required|integer|exists:cpu_turnos,id_turnos',
        ]);

        $data['id_funcionario_que_derivo'] = Auth::id();
        $data['fecha_derivacion'] = Carbon::now();

        $derivacion = CpuDerivacion::create($data);

        return response()->json($derivacion, 201);
    }

    public function update(Request $request, $id)
    {
        $derivacion = CpuDerivacion::find($id);

        if (!$derivacion) {
            return response()->json(['error' => 'Derivación no encontrada'], 404);
        }

        $data = $request->validate([
            'ate_id' => 'integer|exists:cpu_atenciones,id',
            'id_doctor_al_que_derivan' => 'integer|exists:users,id',
            'id_paciente' => 'integer|exists:cpu_personas,id',
            'motivo_derivacion' => 'string',
            'detalle_derivacion' => 'required|string',
            'id_area' => 'integer',
            'fecha_para_atencion' => 'date',
            'hora_para_atencion' => 'date_format:H:i:s',
            'id_estado_derivacion' => 'integer|exists:cpu_estados,id',
            'id_turno_asignado' => 'integer|exists:cpu_turnos,id_turnos',
        ]);

        $derivacion->update($data);

        return response()->json($derivacion);
    }

    public function destroy($id)
    {
        $derivacion = CpuDerivacion::find($id);

        if (!$derivacion) {
            return response()->json(['error' => 'Derivación no encontrada'], 404);
        }

        $derivacion->delete();

        return response()->json(['success' => true]);
    }

    // Método para obtener derivaciones por doctor y rango de fechas
    public function getDerivacionesByDoctorAndDate(Request $request)
    {
        // Validar los parámetros de entrada
        $request->validate([
            'doctor_id' => 'required|integer|exists:users,id',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date',
        ]);

        // Obtener los parámetros de la solicitud
        $doctorId = $request->input('doctor_id');
        $fechaInicio = Carbon::parse($request->input('fecha_inicio'))->startOfDay();
        $fechaFin = Carbon::parse($request->input('fecha_fin'))->endOfDay();

        // Crear la consulta base
        $query = CpuDerivacion::with(['paciente', 'funcionarioQueDerivo'])
            ->whereBetween('fecha_para_atencion', [$fechaInicio, $fechaFin])
            ->whereNotIn('id_estado_derivacion', [4, 5])
            ->select(
                'cpu_personas.id as id_paciente',
                'cpu_personas.cedula',
                'cpu_personas.nombres',
                'cpu_derivaciones.id_turno_asignado',
                'cpu_derivaciones.ate_id',
                'cpu_derivaciones.id as id_derivacion',
                'cpu_derivaciones.fecha_para_atencion',
                'cpu_derivaciones.motivo_derivacion',
                'users.name as funcionario_que_deriva',
                'cpu_derivaciones.hora_para_atencion',
                'cpu_derivaciones.id_estado_derivacion'
            )
            ->join('cpu_personas', 'cpu_personas.id', '=', 'cpu_derivaciones.id_paciente')
            ->join('users', 'users.id', '=', 'cpu_derivaciones.id_funcionario_que_derivo');

        // Agregar las condiciones según el doctor_id
        if ($doctorId == 9) {
            $query->where('id_estado_derivacion', 7);
        } elseif ($doctorId != 1) {
            $query->where('id_doctor_al_que_derivan', $doctorId);
            $query->whereNot('id_estado_derivacion', 2);
        }

        // Ordenar los resultados por fecha y hora ascendentemente
        $query->orderBy('fecha_para_atencion', 'asc')
            ->orderBy('hora_para_atencion', 'asc');

        // Ejecutar la consulta
        $derivaciones = $query->get();

        // Devolver las derivaciones como respuesta JSON
        return response()->json($derivaciones);
    }


    // Método para obtener derivaciones por doctor y rango de fechas
    public function getDerivacionesAll(Request $request)
    {
        // Validar los parámetros de entrada
        $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date',
        ]);

        // Obtener los parámetros de la solicitud
        $fechaInicio = Carbon::parse($request->input('fecha_inicio'))->startOfDay();
        $fechaFin = Carbon::parse($request->input('fecha_fin'))->endOfDay();

        // Consultar las derivaciones según los criterios
        $derivaciones = CpuDerivacion::with(['paciente', 'funcionarioQueDerivo'])
            ->whereBetween('fecha_para_atencion', [$fechaInicio, $fechaFin])
            ->select(
                'cpu_personas.id as id_paciente',
                'cpu_personas.cedula',
                'cpu_personas.nombres',
                'cpu_derivaciones.id as id_derivacion',
                'cpu_derivaciones.fecha_para_atencion',
                'cpu_derivaciones.motivo_derivacion',
                'users.name as funcionario_que_deriva',
                'cpu_derivaciones.hora_para_atencion'
            )
            ->join('cpu_personas', 'cpu_personas.id', '=', 'cpu_derivaciones.id_paciente')
            ->join('users', 'users.id', '=', 'cpu_derivaciones.id_funcionario_que_derivo')
            ->get();

        // Devolver las derivaciones como respuesta JSON
        return response()->json($derivaciones);
    }

    //actualizar estado derivacion
    public function updateDerivacion(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|integer|exists:cpu_derivaciones,id',
            'id_estado_derivacion' => 'required|integer|exists:cpu_estados,id',
        ]);

        $derivacion = CpuDerivacion::find($data['id']);

        if (!$derivacion) {
            return response()->json(['error' => 'Derivación no encontrada'], 404);
        }

        $derivacion->id_estado_derivacion = $data['id_estado_derivacion'];
        $derivacion->save();

        return response()->json(['success' => true, 'derivacion' => $derivacion]);
    }

    public function Reagendar(Request $request)
    {
        $validatedData = $request->validate([
            // Validación de los campos que se enviarán
            'id_turno_asignado' => 'required|integer',
            'ate_id' => 'required|integer',
            'id_doctor_al_que_derivan' => 'required|integer',
            'id_paciente' => 'required|integer',
            'fecha_derivacion' => 'required|date',
            'motivo_derivacion' => 'required|string',
            'detalle_derivacion' => 'nullable|string',
            'id_area' => 'required|integer',
            'fecha_para_atencion' => 'required|date',
            'hora_para_atencion' => 'required',
            'id_funcionario_que_derivo' => 'required|integer',
            'id_estado_derivacion' => 'required|integer',
            'id' => 'required|integer',
            'id_turnos' => 'required|integer',
        ]);

        DB::beginTransaction();

        try {
            // Crear un nuevo registro en cpu_derivaciones
            $derivacion = CpuDerivacion::create([
                'id_turno_asignado' => $validatedData['id_turno_asignado'],
                'ate_id' => $validatedData['ate_id'],
                'id_doctor_al_que_derivan' => $validatedData['id_doctor_al_que_derivan'],
                'id_paciente' => $validatedData['id_paciente'],
                'fecha_derivacion' =>$validatedData['fecha_derivacion'],
                'motivo_derivacion' => $validatedData['motivo_derivacion'],
                'detalle_derivacion' => $validatedData['detalle_derivacion'],
                'id_area' => $validatedData['id_area'],
                'fecha_para_atencion' => $validatedData['fecha_para_atencion'],
                'hora_para_atencion' => $validatedData['hora_para_atencion'],
                'id_funcionario_que_derivo' => $validatedData['id_funcionario_que_derivo'],
                'id_estado_derivacion' => $validatedData['id_estado_derivacion']
            ]);

            // Actualizar el campo id_estado_derivacion en cpu_derivaciones
            CpuDerivacion::where('id', $validatedData['id'])
                ->update(['id_estado_derivacion' => 4]);

            // Actualizar el campo estado en cpu_turnos
            CpuTurno::where('id_turnos', $validatedData['id_turnos'])
                ->where('fehca_turno', '>=', $validatedData['fecha_derivacion'])
                ->update(['estado' => 1]);

            // Actualizar el estado a 7 en cpu_turnos si id_turno_asignado coincide con id_turnos
            CpuTurno::where('id_turnos', $validatedData['id_turno_asignado'])
                ->update(['estado' => 7]);

            DB::commit();

            // Llamar a la función enviarCorreo con los datos necesarios después de que la transacción se haya realizado correctamente
            $this->enviarCorreo($validatedData);

            return response()->json(['message' => 'Registro creado y actualizado correctamente.'], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Error en la operación: ' . $e->getMessage()], 500);
        }
    }

    // Función para actualizar el estado de derivación a 5 (No asistió a la cita)
    public function noAsistioCita($id)
    {
        try {
            // Verificar si el ID existe en la tabla cpu_derivaciones
            $derivacion = CpuDerivacion::find($id);

            if (!$derivacion) {
                return response()->json(['message' => 'Derivación no encontrada.'], 404);
            }

            // Actualizar el campo id_estado_derivacion a 5
            $derivacion->update(['id_estado_derivacion' => 5]);

            return response()->json(['message' => 'Estado de derivación actualizado a 5 correctamente.'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al actualizar el estado de derivación: ' . $e->getMessage()], 500);
        }
    }

    public function enviarCorreoPaciente(Request $validatedData)
    {
        // Obtener los datos necesarios, ajusta esto según tus necesidades
        // $emaile = $request->input("email");
        $emaile = "p1311836587@dn.uleam.edu.ec";
        $nombresd = $validatedData->input("nombres");
        $apellidosd = $validatedData->input("apellidos");
        $monto_otorgadod = $validatedData->input('monto_otorgado');
        $restanted = $validatedData->input('restante');
        $tipo_alimentod = $validatedData->input('tipo_alimento');
        $monto_facturadod = $validatedData->input('monto_facturado');

        $persona = [
            "destinatarios" => $emaile,
            "cc" => "",
            "cco" => "",
            "asunto" => "Consumo de alimentos por ayuda económica - Tasty Uleam",
            "cuerpo" => "<p>Estimado(a) estudiante; La EPE Uleam, registra el consumo de $tipo_alimentod por un valor de $$monto_facturadod dólares; del total de $$monto_otorgadod dólaes, aún tiene disponible $$restanted dolares, saludos cordiales.</p>"
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
        ));

        // Realizar la solicitud cURL
        $resultado = curl_exec($ch);
        $codigoRespuesta = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Procesar la respuesta
        if ($codigoRespuesta === 200) {
            $respuestaDecodificada = json_decode($resultado);
            // Realiza acciones adicionales si es necesario
            $array[0] = 1;
        } else {
            // Manejar errores
            return response()->json(['error' => "Error consultando. Código de respuesta: $codigoRespuesta"], $codigoRespuesta);
        }

        // Devolver una respuesta
        return response()->json(['message' => 'Solicitud enviada correctamente'], 200);
    }
}
