<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CpuTurno;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;

class TurnosController extends Controller
{
    public function agregarTurnos(Request $request)
    {
        $user = Auth::user();
        $area = $user->usr_tipo; // Obtener el área del usuario autenticado
        $doctor = $user->id; // Obtener el ID del doctor del usuario autenticado
        $turnos = $request->input('turnos');
        $response = [
            'agregados' => 0,
            'omitidos' => 0,
        ];

        if ($turnos) {
            foreach ($turnos as $turno) {
                $fechaTurno = $turno['fechaTurno'];
                $horaTurno = $turno['horaTurno'];

                $turnoExistente = CpuTurno::where('id_medico', $doctor)
                    ->where('fehca_turno', $fechaTurno)
                    ->where('hora', $horaTurno)
                    ->where('estado', 1)
                    ->first();

                if ($turnoExistente) {
                    $response['omitidos']++;
                } else {
                    try {
                        $insert = CpuTurno::create([
                            'id_medico' => $doctor,
                            'fehca_turno' => $fechaTurno,
                            'hora' => $horaTurno,
                            'estado' => 1,
                            'area' => $area,
                            'via_atencion' => 1,
                            'usr_date_creacion' => now()
                        ]);

                        if ($insert) {
                            $response['agregados']++;
                        } else {
                            $response['omitidos']++;
                        }
                    } catch (\Exception $e) {
                        $response['omitidos']++;
                    }
                }
            }
        }

        return response()->json($response);
    }

    public function listarTurnos(Request $request)
    {
        $user = Auth::user();
        $id_funcionario = $user->id;
        $ini = $request->input('inicio');
        $hasta = $request->input('hasta');
        $fechaActual = date('Y-m-d');
        $horaActual = date('H:i:s');

        // Depuración
        \Log::info("Usuario: $id_funcionario, Fecha Inicio: $ini, Fecha Fin: $hasta, Fecha Actual: $fechaActual, Hora Actual: $horaActual");

        $turnosQuery = CpuTurno::where('id_medico', $id_funcionario)
            ->where('estado', 1);

        if ($ini == $fechaActual && $hasta == $fechaActual) {
            // Caso 1: Solo hoy, después de la hora actual
            \Log::info("Consulta para el mismo día a partir de la hora actual");
            $turnosQuery->where('fehca_turno', $ini)->where('hora', '>', $horaActual);
        } elseif ($ini == $fechaActual && $hasta > $fechaActual) {
            // Caso 2: Hoy desde la hora actual y días futuros desde cualquier hora
            \Log::info("Consulta para hoy a partir de la hora actual y días futuros desde cualquier hora");
            $turnosQuery->where(function($query) use ($ini, $hasta, $horaActual) {
                $query->where(function($q) use ($ini, $horaActual) {
                    $q->where('fehca_turno', $ini)->where('hora', '>', $horaActual);
                })->orWhere('fehca_turno', '>', $ini);
            });
        } else {
            // Caso 3: Fechas futuras, devolver todos los turnos
            \Log::info("Consulta para fechas futuras sin restricciones de hora");
            $turnosQuery->whereBetween('fehca_turno', [$ini, $hasta]);
        }

        $turnos = $turnosQuery->get();

        // Formatear fechas y horas
        $turnos = $turnos->map(function($turno) {
            $turno->fehca_turno = Carbon::parse($turno->fehca_turno)->format('Y-m-d');
            $turno->hora = Carbon::parse($turno->hora)->format('H:i:s');
            return $turno;
        });

        \Log::info("Turnos encontrados: " . $turnos->count());

        return response()->json($turnos);
    }


    public function listarTurnosPorFuncionario(Request $request)
    {
        $idFuncionario = $request->query('funcionario');
        $fecha = $request->query('fecha');
        $area = $request->query('area');
        $horaActual = Carbon::now()->format('H:i:s');

        // Log para depuración
        \Log::info("Funcionario: $idFuncionario, Fecha: $fecha, Área: $area, Hora Actual: $horaActual");

        $turnosQuery = CpuTurno::where('id_medico', $idFuncionario)
            ->where('estado', 1)
            ->where('area', $area)
            ->whereDate('fehca_turno', $fecha);

        if ($fecha == Carbon::now()->format('Y-m-d')) {
            $turnosQuery->where('hora', '>', $horaActual);
        }

        $turnos = $turnosQuery->get();

        // Depuración de SQL
        \Log::info("Consulta SQL: " . $turnosQuery->toSql());
        \Log::info("Parámetros de consulta: " . json_encode($turnosQuery->getBindings()));

        // Formatear las fechas y horas
        $turnos = $turnos->map(function($turno) {
            $turno->fehca_turno = Carbon::parse($turno->fehca_turno)->format('Y-m-d');
            $turno->hora = Carbon::parse($turno->hora)->format('H:i:s');
            return $turno;
        });

        \Log::info("Turnos encontrados: " . $turnos->count());
        \Log::info("Turnos: " . json_encode($turnos));

        return response()->json($turnos);
    }


    public function eliminarTurno(Request $request)
    {
        $turnoId = $request->input('id');
        $turno = CpuTurno::find($turnoId);
        if ($turno) {
            $turno->estado = 3;
            $turno->usr_date_baja = now();
            $turno->save();

            return response()->json(['success' => true]);
        } else {
            return response()->json(['success' => false], 404);
        }
    }

    public function reservarTurno(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_turno' => 'required|integer|exists:cpu_turnos,id_turnos',
            'id_paciente' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $turno = CpuTurno::find($request->input('id_turno'));
        $turno->id_paciente = $request->input('id_paciente');
        $turno->estado = 7; // Cambia el estado según sea necesario
        $turno->save();

        return response()->json(['success' => true]);
    }
}
