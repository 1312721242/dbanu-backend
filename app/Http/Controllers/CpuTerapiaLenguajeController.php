<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CpuAtencionesTerapiaLenguaje;
use App\Models\CpuAtencion;
use App\Models\CpuDerivacion;
use App\Models\CpuTurno;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class CpuTerapiaLenguajeController extends Controller
{
    /**
     * Guardar una nueva consulta de terapia de lenguaje.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function guardarConsultaTerapia(Request $request)
    {
        DB::beginTransaction();

        try {
            // Agregar log para verificar los datos de la solicitud
            Log::info('Datos recibidos para guardar la consulta de terapia de lenguaje:', $request->all());

            // Validar los datos requeridos
            $validatedData = $request->validate([
                'id_funcionario' => 'required|integer',
                'id_persona' => 'required|integer',
                'medio_atencion' => 'nullable|string',
                'motivo_atencion' => 'required|string',
                'fecha_hora_atencion' => 'nullable|date',
                'anio_atencion' => 'nullable|integer',
                'detalle_atencion' => 'nullable|string',
                'tipo_usuario' => 'nullable|integer',
                'evolucion_enfermedad' => 'nullable|string',
                'diagnostico' => 'nullable|json',
                'prescripcion' => 'nullable|string',
                'recomendacion' => 'nullable|string',
                'tipo_atencion' => 'nullable|string',
                'id_cie10' => 'nullable|integer',
                'id_estado' => 'nullable|integer',
                'id_persona_padre' => 'nullable|integer',
                'id_persona_madre' => 'nullable|integer',
                'numero_hermanos' => 'nullable|integer',
                'antecedentes_embarazo' => 'nullable|array',
                'antecende_parto_nacido' => 'nullable|array',
                'antecedente_morbico' => 'nullable|array',
                'desarollo_psicomotor_lenguaje' => 'nullable|array',
                'mecanismo_oral_periferico' => 'nullable|array',
                'desarrollo_familiar' => 'nullable|array',
                'derivacion_externa' => 'nullable|string',
                'derivacionFlag' => 'nullable|boolean',
                'turno' => 'nullable|integer',
                'area' => 'nullable|integer',
                'funcionario' => 'nullable|integer',
                'motivo_derivacion' => 'nullable|string',
                'fecha_derivacion' => 'nullable|date',
            ]);

            // Crear una nueva instancia de CpuAtencion
            $atencion = new CpuAtencion();
            $atencion->id_funcionario = $validatedData['id_funcionario'];
            $atencion->id_persona = $validatedData['id_persona'];
            $atencion->via_atencion = $validatedData['medio_atencion'] ?? null;
            $atencion->motivo_atencion = $validatedData['motivo_atencion'] ?? null;
            $atencion->fecha_hora_atencion = $validatedData['fecha_hora_atencion'] ?? now();
            $atencion->anio_atencion = $validatedData['anio_atencion'] ?? date('Y');
            $atencion->detalle_atencion = $validatedData['detalle_atencion'] ?? null;
            $atencion->id_tipo_usuario = $validatedData['tipo_usuario'] ?? null;
            $atencion->evolucion_enfermedad = $validatedData['evolucion_enfermedad'] ?? null;
            $atencion->diagnostico = json_encode($validatedData['diagnostico'] ?? []);
            $atencion->prescripcion = $validatedData['prescripcion'] ?? null;
            $atencion->recomendacion = $validatedData['recomendacion'] ?? null;
            $atencion->tipo_atencion = $validatedData['tipo_atencion'] ?? null;
            $atencion->id_cie10 = $validatedData['id_cie10'] ?? null;
            $atencion->id_estado = $validatedData['id_estado'] ?? 1;

            // Guardar la atención
            $atencion->save();
            $this->auditar('cpu_atencion', 'guardarConsultaTerapia', '', $atencion, 'INSERCION', 'Creación de atención');
            // Crear una nueva instancia de CpuAtencionesTerapiaLenguaje
            $terapiaLenguaje = new CpuAtencionesTerapiaLenguaje();
            $terapiaLenguaje->id_atencion = $atencion->id;
            $terapiaLenguaje->id_persona_padre = $validatedData['id_persona_padre'] ?? null;
            $terapiaLenguaje->id_persona_madre = $validatedData['id_persona_madre'] ?? null;
            $terapiaLenguaje->numero_hermanos = $validatedData['numero_hermanos'] ?? null;

            // Convertir arrays a cadenas JSON
            $terapiaLenguaje->antecedentes_embarazo = json_encode($validatedData['antecedentes_embarazo'] ?? []);
            $terapiaLenguaje->antecende_parto_nacido = json_encode($validatedData['antecende_parto_nacido'] ?? []);
            $terapiaLenguaje->antecedente_morbico = json_encode($validatedData['antecedente_morbico'] ?? []);
            $terapiaLenguaje->desarollo_psicomotor_lenguaje = json_encode($validatedData['desarollo_psicomotor_lenguaje'] ?? []);
            $terapiaLenguaje->mecanismo_oral_periferico = json_encode($validatedData['mecanismo_oral_periferico'] ?? []);
            $terapiaLenguaje->desarrollo_familiar = json_encode($validatedData['desarrollo_familiar'] ?? []);
            $terapiaLenguaje->derivacion_externa = $validatedData['derivacion_externa'] ?? null;

            // Guardar datos específicos de Terapia de Lenguaje
            $terapiaLenguaje->save();
            $this->auditar('cpu_atenciones_terapia_lenguaje', 'guardarConsultaTerapia', '', $terapiaLenguaje, 'INSERCION', 'Creación de atención de terapia de lenguaje');
            // Si se activa la derivación interna
            if (!empty($validatedData['derivacionFlag']) && $validatedData['derivacionFlag']) {
                $derivacion = CpuDerivacion::create([
                    'id_doctor_al_que_derivan' => $validatedData['funcionario'],
                    'id_paciente' => $validatedData['id_persona'],
                    'motivo_derivacion' => $validatedData['motivo_derivacion'],
                    'id_area' => $validatedData['area'],
                    'fecha_para_atencion' => $validatedData['fecha_derivacion'] ?? Carbon::now(),
                    'id_turno_asignado' => $validatedData['turno'],
                    'id_estado_derivacion' => 1, // Estado por defecto
                    'id_funcionario_que_derivo' => Auth::id(),
                    'fecha_derivacion' => Carbon::now(),
                    'ate_id' => $atencion->id,
                ]);

                Log::info('Derivación creada exitosamente:', ['derivacion_id' => $derivacion->id]);

                // Actualizar el estado del turno
                $turnoActualizado = CpuTurno::where('id_turnos', $validatedData['turno'])
                    ->update(['estado' => 7]);

                if ($turnoActualizado) {
                    Log::info('Turno actualizado exitosamente', ['turno_id' => $validatedData['turno']]);
                } else {
                    Log::warning('No se pudo actualizar el turno', ['turno_id' => $validatedData['turno']]);
                }
            }

            // Confirmar la transacción
            DB::commit();

            return response()->json([
                'message' => 'Consulta de terapia de lenguaje guardada correctamente con derivación',
                'atencion_id' => $atencion->id,
                'terapia_lenguaje_id' => $terapiaLenguaje->id
            ], 201);

        } catch (\Exception $e) {
            // Si hay un error, deshacer la transacción
            DB::rollBack();
            Log::error('Error al guardar la consulta de terapia de lenguaje: ' . $e->getMessage());
            return response()->json(['error' => 'Error al guardar la consulta de terapia de lenguaje: ' . $e->getMessage()], 500);
        }
    }

    //funcion para auditar
    private function auditar($tabla, $campo, $dataOld, $dataNew, $tipo, $descripcion, $request = null)
    {
        $usuario = $request && !is_string($request) ? $request->user()->name : auth()->user()->name;
        $ip = $request && !is_string($request) ? $request->ip() : request()->ip();
        $ipv4 = gethostbyname(gethostname());
        $publicIp = file_get_contents('http://ipecho.net/plain');
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
        $codigo_auditoria = strtoupper($tabla . '_' . $campo . '_' . $tipo );
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
