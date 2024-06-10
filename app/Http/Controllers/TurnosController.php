<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CpuTurno;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class TurnosController extends Controller
{
    public function agregarTurnos(Request $request)
    {
        $user = auth()->user();
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

        if ($ini == $fechaActual) {
            \Log::info("Consulta para el mismo día");
            $turnos = CpuTurno::where('id_medico', $id_funcionario)
                ->where('estado', 1)
                ->whereBetween('fehca_turno', [$ini, $hasta])
                ->where('hora', '>', $horaActual)
                ->get();
        } else {
            \Log::info("Consulta para diferentes días");
            $turnos = CpuTurno::where('id_medico', $id_funcionario)
                ->where('estado', 1)
                ->whereBetween('fehca_turno', [$ini, $hasta])
                ->get();
        }

        // Formatear las fechas y horas
        $turnos = $turnos->map(function($turno) {
            $turno->fehca_turno = Carbon::parse($turno->fehca_turno)->format('Y-m-d');
            $turno->hora = Carbon::parse($turno->hora)->format('H:i:s');
            return $turno;
        });

        \Log::info("Turnos encontrados: " . $turnos->count());

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
}
