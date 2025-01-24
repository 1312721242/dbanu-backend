<?php

namespace App\Http\Controllers;

use App\Models\CpuAtencion;
use Illuminate\Http\Request;
use App\Models\CpuAtencionFisioterapia;
use App\Models\CpuAtencionTriaje;
use App\Models\CpuCasosMedicos;
use App\Models\CpuDerivacion;
use App\Models\CpuTurno;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class CpuAtencionesFisioterapiaContoller extends Controller
{
    public function guardarAtencionFisioterapia(Request $request)
    {
        // Validar los campos
        $validator = Validator::make($request->all(), [
            'id_funcionario' => 'required|integer',
            'id_paciente' => 'required|integer',
            'id_derivacion' => 'required|integer|exists:cpu_derivaciones,id',
            'partes' => 'required|string',
            'subpartes' => 'required|string',
            'eva' => 'required|integer',
            'test_goniometrico' => 'nullable|json',
            'test_circunferencial' => 'nullable|json',
            'test_longitudinal' => 'nullable|json',
            'valoracion_fisioterapeutica' => 'required|string',
            'diagnostico_fisioterapeutico' => 'required|string',
            'aplicaciones_terapeuticas' => 'nullable|json',
            'tipo_atencion' => 'required|string|in:INICIAL,SUBSECUENTE,REAPERTURA',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        // Log::info('Diagnóstico antes de insertar:', ['diagnostico' => $request->input('diagnostico')]);

        DB::beginTransaction();

        try {
            // Actualizar la derivación
            $derivacion = CpuDerivacion::findOrFail($request->input('id_derivacion'));
            $derivacion->id_estado_derivacion = 2;
            $derivacion->save();

            // Actualizar el estado del turno relacionado
            $turno = CpuTurno::findOrFail($derivacion->id_turno_asignado);
            $turno->estado = 2;
            $turno->save();

            // Guardar caso (si existe id_estado)
            $idCaso = null;

            // Si la atención es INICIAL, se crea un nuevo caso
            if ($request->input('tipo_atencion') === 'INICIAL') {
                if ($request->has('id_estado')) {
                    $caso = new CpuCasosMedicos();
                    $caso->nombre_caso = $request->input('nombre_caso');
                    $caso->id_estado = $request->input('id_estado');
                    $caso->save();
                    $idCaso = $caso->id;
                }
            }
            // Si la atención es SUBSECUENTE, se usa el id_caso del request
            else if ($request->input('tipo_atencion') === 'SUBSECUENTE') {
                $idCaso = $request->input('id_caso'); // Se usa el id_caso enviado en el request

                // Verificar si se envía `informe_final`
                if ($request->has('informe_final')) {
                    $caso = CpuCasosMedicos::findOrFail($idCaso);

                    // Decodificar el nuevo informe enviado
                    $nuevoInforme = json_decode($request->input('informe_final'), true);

                    // Asegurarse de que `informe_final` sea un array antes de agregar la fecha
                    if (!is_array($nuevoInforme)) {
                        return response()->json(['error' => 'Formato inválido en informe_final'], 400);
                    }

                    // Agregar la fecha actual al informe
                    $nuevoInforme['fecha'] = Carbon::now()->toDateTimeString();

                    // Si ya hay algo en `informe_final`, agregar el nuevo informe
                    if (!empty($caso->informe_final)) {
                        $informesPrevios = json_decode($caso->informe_final, true);

                        // Asegurarse de que `informesPrevios` sea un array antes de agregar el nuevo informe
                        if (!is_array($informesPrevios)) {
                            $informesPrevios = [$informesPrevios];
                        }

                        $informesPrevios[] = $nuevoInforme; // Agregar el nuevo informe
                    } else {
                        // Si no hay nada en `informe_final`, simplemente creamos un nuevo array con el nuevo informe
                        $informesPrevios = [$nuevoInforme];
                    }

                    // Actualizar `informe_final` con la nueva información
                    $caso->informe_final = json_encode($informesPrevios);
                    $caso->id_estado = 20; // Actualizar estado del caso
                    $caso->save();
                }
            }
            // Si la atención es REAPERTURAR, se actualiza el estado del caso a 8
            else if ($request->input('tipo_atencion') === 'REAPERTURA') {
                // Asegúrate de obtener el id del caso enviado en la solicitud
                $idCaso = $request->input('id_caso'); // Se usa el id_caso enviado en el request

                if (!$idCaso) {
                    return response()->json(['error' => 'id_caso es requerido para reaperturar un caso.'], 400);
                }

                // Encontrar el caso correspondiente y actualizar su estado
                $caso = CpuCasosMedicos::findOrFail($idCaso);
                $caso->id_estado = 8; // Actualizar el estado del caso a '8' para reapertura
                $caso->save();
            }

            // Guardar la atención
            $atencion = new CpuAtencion();
            $atencion->id_funcionario = $request->input('id_funcionario');
            $atencion->id_persona = $request->input('id_paciente');
            $atencion->via_atencion = $request->input('via_atencion');
            $atencion->motivo_atencion = $request->input('motivo');
            $atencion->id_tipo_usuario = $request->input('id_tipo_usuario');
            $atencion->diagnostico = is_array($request->diagnostico) ? json_encode($request->diagnostico) : $request->diagnostico;
            $atencion->detalle_atencion = 'Atención Nutrición';
            $atencion->fecha_hora_atencion = Carbon::now();
            $atencion->anio_atencion = Carbon::now()->year;
            // $atencion->recomendacion = $request->input('recomendaciones');
            $atencion->tipo_atencion = $request->input('tipo_atencion');
            $atencion->id_caso = $idCaso;
            $atencion->save();

            $triaje = CpuAtencionTriaje::where('id_derivacion', $request->input('id_derivacion'))->first();
            $updateData = [
                'talla' => $request->input('talla'),
                'peso' => $request->input('peso'),
                'temperatura' => $request->input('temperatura'),
                'saturacion' => $request->input('saturacion'),
                'presion_sistolica' => $request->input('presion_sistolica'),
                'presion_diastolica' => $request->input('presion_diastolica'),
            ];

            if ($triaje) {
                foreach ($updateData as $key => $value) {
                    if ($triaje[$key] != $value) {
                        $triaje[$key] = $value;
                    }
                }
                $triaje->save();
            } else {
                $updateData['id_derivacion'] = $request->input('id_derivacion');
                CpuAtencionTriaje::create($updateData);
            }

            // Guardar la atención fisioterapia
            Log::info('ID_DERIVACION:', ['id_derivacion' => $request->input('id_derivacion')]);
            $fisioterapia = new CpuAtencionFisioterapia();
            $fisioterapia->id_derivacion = $request->id_derivacion;
            $fisioterapia->partes = $request->input('partes');
            $fisioterapia->subpartes = $request->input('subpartes');
            $fisioterapia->eva = $request->input('eva');
            $fisioterapia->test_goniometrico = json_decode($request->input('test_goniometrico'), true);
            $fisioterapia->test_circunferencial = json_decode($request->input('test_circunferencial'), true);
            $fisioterapia->test_longitudinal = json_decode($request->input('test_longitudinal'), true);
            $fisioterapia->valoracion_fisioterapeutica = $request->input('valoracion_fisioterapeutica');
            $fisioterapia->diagnostico_fisioterapeutico = $request->input('diagnostico_fisioterapeutico');
            $fisioterapia->aplicaciones_terapeuticas = json_decode($request->input('aplicaciones_terapeuticas'), true);
            $fisioterapia->save();

            // Verificar si se envía el `id_turno_asignado`
            if ($request->filled('id_turno_asignado')) {
                Log::info('Valor de id_turno_asignado:', ['id_turno_asignado' => $request->input('id_turno_asignado')]);
                try {
                    // Validar los datos de derivación
                    $derivacionData = $request->validate([
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

                    // Lógica adicional si la validación es exitosa
                    $derivacionData['ate_id'] = $atencion->id;
                    $derivacionData['id_funcionario_que_derivo'] = $request->input('id_funcionario');
                    $derivacionData['fecha_derivacion'] = Carbon::now();
                    $derivacion = CpuDerivacion::create($derivacionData);

                    // Actualizar el estado del turno relacionado
                    // $turno = CpuTurno::findOrFail($derivacionData['id_turno_asignado']);
                    // $turno->estado = 2; // Actualiza el estado del turno a 2
                    // $turno->save();
                    if ($request->filled('id_turno_asignado')) {
                        $turno = CpuTurno::findOrFail($request->input('id_turno_asignado'));
                        $turno->estado = 2; // Actualiza el estado del turno
                        $turno->save();
                    }
                } catch (\Illuminate\Validation\ValidationException $e) {
                    // Capturar los errores de validación y devolver una respuesta JSON
                    return response()->json([
                        'error' => 'Error de validación',
                        'messages' => $e->errors(), // Aquí se devuelven los detalles de los errores
                    ], 422);
                }
            }

            DB::commit();

            return response()->json(['success' => true, 'nutricion_id' => $fisioterapia->id]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al guardar la atención fisioterapia:', ['exception' => $e->getMessage()]);
            return response()->json(['error' => 'Error al guardar la atención fisioterapia'], 500);
        }
    }
}
