<?php

namespace App\Http\Controllers;

use App\Models\CpuAtencion;
use Illuminate\Http\Request;
use App\Models\CpuDerivacion;
use App\Models\CpuTurno;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;

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
            // 'detalle_derivacion' => 'required|string',
            'id_area' => 'required|integer',
            'fecha_para_atencion' => 'required|date',
            'hora_para_atencion' => 'required|date_format:H:i:s',
            'id_estado_derivacion' => 'integer|exists:cpu_estados,id',
            'id_turno_asignado' => 'required|integer|exists:cpu_turnos,id_turnos',
        ]);

        $data['id_funcionario_que_derivo'] = Auth::id();
        $data['fecha_derivacion'] = Carbon::now();

        $derivacion = CpuDerivacion::create($data);

        if ($derivacion) {
            // Actualizar la tabla de turnos
            DB::table('cpu_turnos')
                ->where('id_turnos', $data['id_turno_asignado'])
                ->update([
                    'estado' => $data['id_estado_derivacion'],
                    'id_paciente' => $data['id_paciente']
                ]);
        }
        $this->auditar('cpu_derivacion', 'store', '', $derivacion, 'INSERCION', 'Creación de derivación');

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
            // 'detalle_derivacion' => 'required|string',
            'id_area' => 'integer',
            'fecha_para_atencion' => 'date',
            'hora_para_atencion' => 'date_format:H:i:s',
            'id_estado_derivacion' => 'integer|exists:cpu_estados,id',
            'id_turno_asignado' => 'integer|exists:cpu_turnos,id_turnos',
        ]);

        $derivacion->update($data);
        $this->auditar('cpu_derivacion', 'update', '', $derivacion, 'MODIFICACION', 'Actualización de derivación');

        return response()->json($derivacion);
    }

    public function destroy($id)
    {
        $derivacion = CpuDerivacion::find($id);

        if (!$derivacion) {
            return response()->json(['error' => 'Derivación no encontrada'], 404);
        }

        $derivacion->delete();
        $this->auditar('cpu_derivacion', 'destroy', '', $derivacion, 'ELIMINACION', 'Eliminación de derivación', $id);
        return response()->json(['success' => true]);
    }

    // Método para obtener derivaciones por doctor y rango de fechas
    // public function getDerivacionesByDoctorAndDate(Request $request)
    // {
    //     // Validar los parámetros de entrada
    //     $request->validate([
    //         'doctor_id' => 'required|integer|exists:users,id',
    //         'fecha_inicio' => 'required|date',
    //         'fecha_fin' => 'required|date',
    //     ]);

    //     // Obtener los parámetros de la solicitud
    //     $doctorId = $request->input('doctor_id');
    //     $fechaInicio = Carbon::parse($request->input('fecha_inicio'))->startOfDay();
    //     $fechaFin = Carbon::parse($request->input('fecha_fin'))->endOfDay();

    //     $usrSede = Auth::user()->usr_sede;
    //     Log::info("Sede del usuario autenticado: $usrSede");

    //     // Crear la consulta base
    //     $query = CpuDerivacion::with(['paciente', 'funcionarioQueDerivo'])
    //         ->whereBetween('fecha_para_atencion', [$fechaInicio, $fechaFin])
    //         ->whereNotIn('id_estado_derivacion', [4, 5])
    //         ->select(
    //             'cpu_personas.id as id_paciente',
    //             'cpu_personas.cedula',
    //             'cpu_personas.nombres',
    //             'cpu_personas.sexo',
    //             'cpu_personas.id_clasificacion_tipo_usuario',
    //             'cpu_derivaciones.id_turno_asignado',
    //             'cpu_derivaciones.ate_id',
    //             'cpu_derivaciones.id as id_derivacion',
    //             'cpu_derivaciones.fecha_para_atencion',
    //             'cpu_derivaciones.motivo_derivacion',
    //             'cpu_atenciones.diagnostico',
    //             'cpu_derivaciones.id_tramite',
    //             'users.name as funcionario_que_deriva',
    //             'users.name as funcionario_al_que_deriva',
    //             'cpu_userrole.role as area_atencion',
    //             'cpu_derivaciones.hora_para_atencion',
    //             'cpu_derivaciones.id_estado_derivacion',
    //             'users.email as funcionario_email',
    //             'users.usr_sede as id_sede',
    //             DB::raw('COALESCE(cpu_datos_estudiantes.email_institucional, cpu_datos_empleados.emailinstitucional, cpu_datos_usuarios_externos.email) as email_paciente')
    //         )
    //         ->join('cpu_personas', 'cpu_personas.id', '=', 'cpu_derivaciones.id_paciente')
    //         ->join('users', 'users.id', '=', 'cpu_derivaciones.id_funcionario_que_derivo')
    //         ->leftJoin('users as deriva_usuario', 'deriva_usuario.id', '=', 'cpu_derivaciones.id_doctor_al_que_derivan')
    //         ->leftJoin('cpu_userrole', 'cpu_derivaciones.id_area', '=', 'cpu_userrole.id_userrole')
    //         ->leftJoin('cpu_datos_estudiantes', 'cpu_personas.id', '=', 'cpu_datos_estudiantes.id_persona')
    //         ->leftJoin('cpu_datos_empleados', 'cpu_personas.id', '=', 'cpu_datos_empleados.id_persona')
    //         ->leftJoin('users as deriva_sede', 'users.usr_sede', '=', 'cpu_derivaciones.id_funcionario_que_derivo')
    //         ->leftJoin('cpu_atenciones', 'cpu_atenciones.id', '=', 'cpu_derivaciones.ate_id')
    //         ->leftJoin('cpu_datos_usuarios_externos', 'cpu_personas.id', '=', 'cpu_datos_usuarios_externos.id_persona')
    //         ->distinct(); // Asegurar que los registros sean únicos

    //     // Agregar las condiciones según el doctor_id
    //     if ($doctorId == 9) {
    //         $query->where('id_estado_derivacion', 7)
    //                 ->where('users.usr_sede', $usrSede);
    //     } elseif ($doctorId != 1) {
    //         $query->where('id_doctor_al_que_derivan', $doctorId);
    //         $query->whereNot('id_estado_derivacion', 2);
    //     } elseif ('users.usr_sede' > 2) {
    //         $query->where('id_estado_derivacion', 7);
    //     }

    //     // Ordenar los resultados por fecha y hora ascendentemente
    //     $query->orderBy('fecha_para_atencion', 'asc')
    //         ->orderBy('hora_para_atencion', 'asc');

    //     // Ejecutar la consulta
    //     $derivaciones = $query->get();

    //     // Verificar si hay datos en 'id_tramite' y obtener información adicional si es necesario
    //     foreach ($derivaciones as $derivacion) {
    //         if (!empty($derivacion->id_tramite)) {
    //             $tramite = DB::table('cpu_tramites')
    //                 ->select('tra_link_receptado', 'tra_link_enviado')
    //                 ->where('id_tramite', $derivacion->id_tramite)
    //                 ->first();

    //             if ($tramite) {
    //                 $derivacion->tra_link_receptado = $tramite->tra_link_receptado;
    //                 $derivacion->tra_link_enviado = $tramite->tra_link_enviado;
    //             }
    //         }
    //     }

    //     // Devolver las derivaciones como respuesta JSON
    //     return response()->json($derivaciones);
    // }

    public function getDerivacionesByDoctorAndDate(Request $request)
    {
        // Validación
        $request->validate([
            'doctor_id'    => 'required|integer|exists:users,id',
            'fecha_inicio' => 'required|date',
            'fecha_fin'    => 'required|date',
        ]);

        $doctorId    = $request->input('doctor_id');
        $fechaInicio = Carbon::parse($request->input('fecha_inicio'))->startOfDay();
        $fechaFin    = Carbon::parse($request->input('fecha_fin'))->endOfDay();

        $usrSede = Auth::user()->usr_sede;
        Log::info("Sede del usuario autenticado: $usrSede");

        // Consulta base
        $query = CpuDerivacion::with(['paciente', 'funcionarioQueDerivo'])
            ->whereBetween('fecha_para_atencion', [$fechaInicio, $fechaFin])
            ->whereNotIn('id_estado_derivacion', [4, 5])
            ->select(
                'cpu_personas.id as id_paciente',
                'cpu_personas.cedula',
                'cpu_personas.nombres',
                'cpu_personas.sexo',
                'cpu_personas.id_clasificacion_tipo_usuario',
                'cpu_derivaciones.id_turno_asignado',
                'cpu_derivaciones.ate_id',
                'cpu_derivaciones.id as id_derivacion',
                'cpu_derivaciones.fecha_para_atencion',
                'cpu_derivaciones.motivo_derivacion',
                'cpu_atenciones.diagnostico',
                'cpu_derivaciones.id_tramite',
                'users.name as funcionario_que_deriva',
                'users.name as funcionario_al_que_deriva',
                'cpu_userrole.role as area_atencion',
                'cpu_derivaciones.hora_para_atencion',
                'cpu_derivaciones.id_estado_derivacion',
                'users.email as funcionario_email',
                'users.usr_sede as id_sede',
                DB::raw('COALESCE(cpu_datos_estudiantes.email_institucional, cpu_datos_empleados.emailinstitucional, cpu_datos_usuarios_externos.email) as email_paciente'),
                // ── Campos de la tabla de turnos ──
                'cpu_turnos.tipo_atencion as turno_tipo_atencion',
                'cpu_turnos.fehca_turno as turno_fecha',
                'cpu_turnos.hora as turno_hora'
            )
            ->join('cpu_personas', 'cpu_personas.id', '=', 'cpu_derivaciones.id_paciente')
            ->join('users', 'users.id', '=', 'cpu_derivaciones.id_funcionario_que_derivo')
            ->leftJoin('users as deriva_usuario', 'deriva_usuario.id', '=', 'cpu_derivaciones.id_doctor_al_que_derivan')
            ->leftJoin('cpu_userrole', 'cpu_derivaciones.id_area', '=', 'cpu_userrole.id_userrole')
            ->leftJoin('cpu_datos_estudiantes', 'cpu_personas.id', '=', 'cpu_datos_estudiantes.id_persona')
            ->leftJoin('cpu_datos_empleados', 'cpu_personas.id', '=', 'cpu_datos_empleados.id_persona')
            ->leftJoin('cpu_atenciones', 'cpu_atenciones.id', '=', 'cpu_derivaciones.ate_id')
            ->leftJoin('cpu_datos_usuarios_externos', 'cpu_personas.id', '=', 'cpu_datos_usuarios_externos.id_persona')
            // 🔗 Join con la tabla de turnos por el turno asignado
            ->leftJoin('cpu_turnos', 'cpu_turnos.id_turnos', '=', 'cpu_derivaciones.id_turno_asignado')
            ->distinct();

        // Filtros por doctor/rol
        if ($doctorId == 9) {
            $query->where('id_estado_derivacion', 7)
                ->where('users.usr_sede', $usrSede);
        } elseif ($doctorId != 1) {
            $query->where('id_doctor_al_que_derivan', $doctorId)
                ->whereNot('id_estado_derivacion', 2);
        } elseif ($usrSede > 2) { // ← fix del string literal
            $query->where('id_estado_derivacion', 7);
        }

        // Orden
        $query->orderBy('fecha_para_atencion', 'asc')
            ->orderBy('hora_para_atencion', 'asc');

        $derivaciones = $query->get();

        // Enriquecer con links de trámite si aplica
        foreach ($derivaciones as $derivacion) {
            if (!empty($derivacion->id_tramite)) {
                $tramite = DB::table('cpu_tramites')
                    ->select('tra_link_receptado', 'tra_link_enviado')
                    ->where('id_tramite', $derivacion->id_tramite)
                    ->first();

                if ($tramite) {
                    $derivacion->tra_link_receptado = $tramite->tra_link_receptado;
                    $derivacion->tra_link_enviado   = $tramite->tra_link_enviado;
                }
            }
        }

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
        $this->auditar('cpu_derivacion', 'updateDerivacion', '', $derivacion, 'MODIFICACION', 'Actualización de derivación');
        return response()->json(['success' => true, 'derivacion' => $derivacion]);
    }

    public function reagendar(Request $request)
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
            'email_paciente' => 'required|string',
            'funcionario_email' => 'required|string',
            'nombres_paciente' => 'required|string',
            'nombres_funcionario' => 'required|string',
            'area_atencion' => 'required|string',

        ]);

        Log::info('Datos recibidos en reagendar:', $validatedData);

        DB::beginTransaction();

        try {
            // Crear un nuevo registro en cpu_derivaciones
            $derivacion = CpuDerivacion::create([
                'id_turno_asignado' => $validatedData['id_turno_asignado'],
                'ate_id' => $validatedData['ate_id'],
                'id_doctor_al_que_derivan' => $validatedData['id_doctor_al_que_derivan'],
                'id_paciente' => $validatedData['id_paciente'],
                'fecha_derivacion' => $validatedData['fecha_derivacion'],
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

            // ✅ Actualizar el estado de la atención como "reagendada" (id_estado = 4)
            CpuAtencion::where('id', $validatedData['ate_id'])->update(['id_estado' => 4]);

            // Llamar a la función enviarCorreo con los datos necesarios después de que la transacción se haya realizado correctamente
            // $this->enviarCorreoPaciente($validatedData, 'reagendamiento');
            // $this->enviarCorreoFuncionario($validatedData, 'reagendamiento');
            $this->auditar('cpu_derivacion', 'Reagendar', '', $derivacion, 'INSERCION', 'Creación de derivación');

            // Si todo va bien, confirmar la transacción
            DB::commit();

            // Enviar correos después de confirmar la transacción
            $correoController = new CpuCorreoEnviadoController();
            $correoController->enviarCorreoPaciente($validatedData, 'reagendamiento');
            $correoController->enviarCorreoFuncionario($validatedData, 'reagendamiento');

            return response()->json(['message' => 'Registro creado y actualizado correctamente.'], 201);
        } catch (\Exception $e) {

            // Si algo falla, revertir todas las operaciones
            DB::rollBack();
            return response()->json(['error' => 'Error en la operación: ' . $e->getMessage()], 500);
        }
    }

    // Función para actualizar el estado de derivación a 5 (No asistió a la cita)
    // public function noAsistioCita(Request $request, $id)
    // {
    //     // Validar los datos de entrada
    //     $validatedData = $request->validate([
    //         'email_paciente' => 'required|string',
    //         'area_atencion' => 'required|string',
    //         'fecha_para_atencion' => 'required|date',
    //         'hora_para_atencion' => 'required|string',
    //         'nombres_funcionario' => 'required|string',
    //         'nombres_paciente' => 'required|string',
    //         'funcionario_email' => 'required|string',
    //     ]);

    //     DB::beginTransaction();

    //     try {
    //         // Verificar si el ID existe en la tabla cpu_derivaciones
    //         $derivacion = CpuDerivacion::find($id);

    //         if (!$derivacion) {
    //             return response()->json(['message' => 'Derivación no encontrada.'], 404);
    //         }

    //         // Actualizar el campo id_estado_derivacion a 5
    //         $derivacion->update(['id_estado_derivacion' => 5]);

    //         // Enviar correos al paciente y al funcionario utilizando los datos validados
    //         // $this->enviarCorreoPaciente($validatedData, 'no_show');
    //         // $this->enviarCorreoFuncionario($validatedData, 'no_show');

    //         DB::commit();

    //         // Enviar correos después de confirmar la transacción
    //         $correoController = new CpuCorreoEnviadoController();
    //         $correoController->enviarCorreoPaciente($validatedData, 'no_show');
    //         $correoController->enviarCorreoFuncionario($validatedData, 'no_show');

    //         return response()->json(['message' => 'Estado de derivación actualizado a 5 correctamente y correos enviados.'], 200);
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         return response()->json(['error' => 'Error al actualizar el estado de derivación: ' . $e->getMessage()], 500);
    //     }
    // }

    // public function noAsistioCita(Request $request, $id)
    // {
    //     // 🔍 Imprimir los datos recibidos antes de la validación
    //     Log::info('📌 Datos recibidos en noAsistioCita:', $request->all());

    //     // 🔥 Evita que Laravel transforme `""` en `null`
    //     $validatedData = $request->all();

    //     // Si `id_paciente` no existe o es `null`, lánzalo manualmente
    //     if (!isset($validatedData['id_paciente'])) {
    //         Log::error('❌ Error: id_paciente no está presente en los datos.');
    //         return response()->json(['error' => 'Falta el campo id_paciente'], 400);
    //     }

    //     // 🔥 Validación manual para mayor control
    //     $rules = [
    //         'id_paciente' => 'required|integer',
    //         'email_paciente' => 'required|string',
    //         'area_atencion' => 'required|string',
    //         'fecha_para_atencion' => 'required|date',
    //         'hora_para_atencion' => 'required|string',
    //         'nombres_funcionario' => 'required|string',
    //         'nombres_paciente' => 'required|string',
    //         'funcionario_email' => 'required|string',
    //     ];
    //     $validator = Validator::make($validatedData, $rules);

    //     if ($validator->fails()) {
    //         Log::error('❌ Error en validación:', ['errors' => $validator->errors()]);
    //         return response()->json(['error' => $validator->errors()], 400);
    //     }

    //     DB::beginTransaction();

    //     try {
    //         // Buscar derivación
    //         $derivacion = CpuDerivacion::find($id);
    //         if (!$derivacion) {
    //             return response()->json(['message' => 'Derivación no encontrada.'], 404);
    //         }

    //         // Actualizar estado de la derivación
    //         $derivacion->update(['id_estado_derivacion' => 5]);

    //         // Enviar correos al paciente y al funcionario utilizando los datos validados
    //         $this->enviarCorreoPaciente($validatedData, 'no_show');
    //         $this->enviarCorreoFuncionario($validatedData, 'no_show');

    //         DB::commit();
    //         return response()->json(['message' => 'Estado actualizado y correos enviados.'], 200);
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         Log::error('❌ Error en la transacción:', ['error' => $e->getMessage()]);
    //         return response()->json(['error' => 'Error en la operación: ' . $e->getMessage()], 500);
    //     }
    // }

    public function noAsistioCita(Request $request, $id)
    {
        Log::info('Datos recibidos en noAsistioCita:', $request->all());

        $validatedData = $request->all();

        if (!isset($validatedData['id_paciente'])) {
            Log::error('Error: id_paciente no está presente en los datos.');
            return response()->json(['error' => 'Falta el campo id_paciente'], 400);
        }

        $rules = [
            'id_paciente' => 'required|integer',
            'email_paciente' => 'required|string',
            'area_atencion' => 'required|string',
            'fecha_para_atencion' => 'required|date',
            'hora_para_atencion' => 'required|string',
            'nombres_funcionario' => 'required|string',
            'nombres_paciente' => 'required|string',
            'funcionario_email' => 'required|string',
        ];
        $validator = Validator::make($validatedData, $rules);

        if ($validator->fails()) {
            Log::error('Error en validación:', ['errors' => $validator->errors()]);
            return response()->json(['error' => $validator->errors()], 400);
        }

        DB::beginTransaction();

        try {
            // Buscar derivación
            $derivacion = CpuDerivacion::find($id);
            if (!$derivacion) {
                return response()->json(['message' => 'Derivación no encontrada.'], 404);
            }

            // Actualizar estado de la derivación
            $derivacion->update(['id_estado_derivacion' => 5]);

            // 🔁 Actualizar estado del turno si existe
            if ($derivacion->id_turno_asignado) {
                $turno = \App\Models\CpuTurno::find($derivacion->id_turno_asignado);
                if ($turno) {
                    // Combinar fecha y hora del turno
                    $fechaHoraTurno = \Carbon\Carbon::parse($turno->fehca_turno->format('Y-m-d') . ' ' . $turno->hora);

                    if ($fechaHoraTurno->isFuture()) {
                        $turno->estado = 1; // Disponible nuevamente
                    } else {
                        $turno->estado = 5; // No asistió / vencido
                    }

                    $turno->save();
                    Log::info("📅 Turno {$turno->id_turnos} actualizado a estado {$turno->estado}");
                } else {
                    Log::warning("⚠️ Turno con ID {$derivacion->id_turno_asignado} no encontrado.");
                }
            }

            // Enviar correos
            $this->enviarCorreoPaciente($validatedData, 'no_show');
            $this->enviarCorreoFuncionario($validatedData, 'no_show');

            DB::commit();
            return response()->json(['message' => 'Estado actualizado, turno ajustado y correos enviados.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error en la transacción:', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Error en la operación: ' . $e->getMessage()], 500);
        }
    }

    public function enviarCorreoPaciente(array $validatedData, $tipo)
    {
        Log::info('Datos correo al paciente', [
            'data' => $validatedData,
            'tipo' => $tipo
        ]);

        try {
            // Usar el email que ya viene en $validatedData
            $email_paciente = $validatedData['email_paciente'];
            $area_atencion = $validatedData['area_atencion'];
            $fecha_para_atencion = $validatedData['fecha_para_atencion'];
            $hora_para_atencion = $validatedData['hora_para_atencion'];
            $nombres_funcionario = $validatedData['nombres_funcionario'];

            // 📌 Construir asunto y cuerpo del correo
            if ($tipo === 'no_show') {
                $asunto = "Cita no asistida en el área de $area_atencion";
                $cuerpo = "<p>Estimado(a) ciudadano(a); La Dirección de Bienestar, Admisión y Nivelación Universitaria, informa que no asistió a su cita programada para el área de <strong>$area_atencion</strong> el día <strong>$fecha_para_atencion</strong> a las <strong>$hora_para_atencion</strong>. Si desea reagendar su cita, por favor acérquese al área de salud de la dirección.<br><br>Saludos cordiales.</p>";
            } else {
                $asunto = "Reagendamiento de cita programada para el área de $area_atencion";
                $cuerpo = "<p>Estimado(a) ciudadano(a); La Dirección de Bienestar, Admisión y Nivelación Universitaria, registra el reagendamiento de una cita programada para el área de <strong>$area_atencion</strong> para la fecha <strong>$fecha_para_atencion</strong> a las <strong>$hora_para_atencion</strong> con el funcionario <strong>$nombres_funcionario</strong>. Por favor presentarse 15 minutos antes.<br><br>Saludos cordiales.</p>";
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
                return response()->json(['error' => 'No se pudo obtener el token de autenticación'], 500);
            }

            $accessToken = $tokenResponse['access_token'];

            // 2. Enviar el correo vía Graph API con el remitente bienestar@uleam.edu.ec
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
                Log::info('Correo enviado al paciente (Graph)', ['email' => $email_paciente]);
                return response()->json(['message' => 'Correo enviado correctamente'], 200);
            } else {
                Log::error('Error al enviar correo al paciente (Graph)', [
                    'status' => $sendResponse->status(),
                    'response' => $sendResponse->body()
                ]);
                return response()->json(['error' => 'Error al enviar el correo'], $sendResponse->status());
            }
        } catch (\Exception $e) {
            Log::error('Excepción al enviar correo al paciente (Graph)', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $validatedData
            ]);
            return response()->json(['error' => 'Error interno al enviar el correo'], 500);
        }
    }

    public function enviarCorreoFuncionario(array $validatedData, $tipo)
    {
        Log::info('Datos correo al funcionario', [
            'data' => $validatedData,
            'tipo' => $tipo
        ]);

        try {
            // 📌 Usar el email que ya viene en $validatedData
            $funcionario_email = $validatedData['funcionario_email'];
            $area_atencion = $validatedData['area_atencion'];
            $fecha_para_atencion = $validatedData['fecha_para_atencion'];
            $hora_para_atencion = $validatedData['hora_para_atencion'];
            $nombres_paciente = $validatedData['nombres_paciente'];

            // 📌 Construir asunto y cuerpo
            if ($tipo === 'no_show') {
                $asunto = "Paciente no asistió a la cita en el área de $area_atencion";
                $cuerpo = "<p>Estimado(a) funcionario(a); La Dirección de Bienestar, Admisión y Nivelación Universitaria, informa que el ciudadano(a) <strong>$nombres_paciente</strong> no asistió a su cita programada para el área de <strong>$area_atencion</strong> el día <strong>$fecha_para_atencion</strong> a las <strong>$hora_para_atencion</strong>.<br><br>Saludos cordiales.</p>";
            } else {
                $asunto = "Reagendamiento de cita programada para el área de $area_atencion";
                $cuerpo = "<p>Estimado(a) funcionario(a); La Dirección de Bienestar, Admisión y Nivelación Universitaria, registra el reagendamiento de una cita para el área de <strong>$area_atencion</strong> para la fecha <strong>$fecha_para_atencion</strong> a las <strong>$hora_para_atencion</strong> con el ciudadano(a) <strong>$nombres_paciente</strong>.<br><br>Saludos cordiales.</p>";
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
                return response()->json(['error' => 'No se pudo obtener el token de autenticación'], 500);
            }

            $accessToken = $tokenResponse['access_token'];

            // 2. Enviar el correo vía Graph API con el remitente bienestar@uleam.edu.ec
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
                return response()->json(['message' => 'Correo enviado correctamente'], 200);
            } else {
                Log::error('Error al enviar correo al funcionario', [
                    'status' => $sendResponse->status(),
                    'response' => $sendResponse->body()
                ]);
                return response()->json(['error' => 'Error al enviar el correo'], $sendResponse->status());
            }
        } catch (\Exception $e) {
            Log::error('Excepción al enviar correo al funcionario (Graph)', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $validatedData
            ]);
            return response()->json(['error' => 'Error interno al enviar el correo'], 500);
        }
    }

    //funcion para auditar
    private function auditar($tabla, $campo, $dataOld, $dataNew, $tipo, $descripcion, $request = null)
    {
        $usuario = $request && !is_string($request) ? $request->user()->name : auth()->user()->name;
        $ip = $request && !is_string($request) ? $request->ip() : request()->ip();
        $ipv4 = gethostbyname(gethostname());
        $publicIp = file_get_contents('https://ifconfig.me/ip');
        $ioConcatenadas = 'IP LOCAL: ' . $ip . '  --IPV4: ' . $ipv4 . '  --IP PUBLICA: ' . $publicIp;
        $nombreequipo = gethostbyaddr($ip);
        $userAgent = $request && !is_string($request) ? $request->header('User-Agent') : request()->header('User-Agent');
        $tipoEquipo = 'Desconocido';

        if (stripos($userAgent, 'Mobile') !== false) {
            $tipoEquipo = 'Celular';
        } elseif (stripos($userAgent, 'Tablet') !== false) {
            $tipoEquipo = 'Tablet';
        } elseif (stripos($userAgent, 'Laptop') !== false || stripos($userAgent, 'Macintosh') !== false) {
            $tipoEquipo = 'Laptop';
        } elseif (stripos($userAgent, 'Windows') !== false || stripos($userAgent, 'Linux') !== false) {
            $tipoEquipo = 'Computador de Escritorio';
        }
        $nombreUsuarioEquipo = get_current_user() . ' en ' . $tipoEquipo;

        $fecha = now();
        $codigo_auditoria = strtoupper($tabla . '_' . $campo . '_' . $tipo);
        DB::table('cpu_auditoria')->insert([
            'aud_user' => $usuario,
            'aud_tabla' => $tabla,
            'aud_campo' => $campo,
            'aud_dataold' => $dataOld,
            'aud_datanew' => $dataNew,
            'aud_tipo' => $tipo,
            'aud_fecha' => $fecha,
            'aud_ip' => $ioConcatenadas,
            'aud_tipoauditoria' => $this->getTipoAuditoria($tipo),
            'aud_descripcion' => $descripcion,
            'aud_nombreequipo' => $nombreequipo,
            'aud_descrequipo' => $nombreUsuarioEquipo,
            'aud_codigo' => $codigo_auditoria,
            'created_at' => now(),
            'updated_at' => now(),

        ]);
    }

    private function getTipoAuditoria($tipo)
    {
        switch ($tipo) {
            case 'CONSULTA':
                return 1;
            case 'INSERCION':
                return 3;
            case 'MODIFICACION':
                return 2;
            case 'ELIMINACION':
                return 4;
            case 'LOGIN':
                return 5;
            case 'LOGOUT':
                return 6;
            case 'DESACTIVACION':
                return 7;
            default:
                return 0;
        }
    }
}
