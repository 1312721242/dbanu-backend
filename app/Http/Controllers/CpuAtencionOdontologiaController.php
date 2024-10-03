<?php
namespace App\Http\Controllers;
use App\Models\CpuAtencionOdontologia;
use App\Models\CpuAtencion;
use App\Models\CpuDiente;
use App\Models\CpuDerivacion;
use App\Models\CpuAtencionTriaje;
use App\Models\CpuTurno;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
class CpuAtencionOdontologiaController extends Controller
{
    public function store(Request $request)
    {
        try {
            \Log::info('Datos recibidos para atención odontológica', $request->all());
            DB::beginTransaction();
            // Validar los datos del request, permitiendo que los campos de diagnóstico y otros sean opcionales
            $validator = Validator::make($request->all(), [
                'atencion.id_persona' => 'required|integer|exists:cpu_personas,id',
                'atencion.id_funcionario' => 'required|integer|exists:users,id',
                'atencion.via_atencion' => 'required|string',
                'atencion.motivo_atencion' => 'required|string',
                'atencion.enfermedad_proble_actual' => 'nullable|string',
                'odontograma' => 'required|array',
                'odontograma.adulto' => 'required|array|min:1',
                'diagnostico' => 'nullable|array|min:0',
                'examen_estomatognatico' => 'nullable|array|min:0',
                'tratamientos' => 'nullable|array|min:0',
                'planes' => 'nullable|array|min:0',
                'planes.biometria' => 'nullable|array',
                'planes.quimica_sanguinea' => 'nullable|array',
                'planes.rayos_x' => 'nullable|array',
                'planes.otros' => 'nullable|array',
            
            ]);
            
            
            // Validación de fallos
            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Error en la validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Crear la atención general
            $atencion = CpuAtencion::create([
                'id_persona' => $request->atencion['id_persona'],
                'id_funcionario' => $request->atencion['id_funcionario'], // Solo almacenar el id_funcionario
                'via_atencion' => $request->atencion['via_atencion'],
                'motivo_atencion' => $request->atencion['motivo_atencion'],
                'fecha_hora_atencion' => now(),
                'anio_atencion' => now()->year,
                'diagnostico' => !empty($request->diagnostico) ? json_encode($request->diagnostico) : null,
                'id_estado' =>1,
            ]);

            // Manejar el registro del odontograma (adultos)
            $arcada = ['adulto' => $request->odontograma['adulto']];

            // Verificar si el paciente ya tiene un registro de dientes
            $cpuDiente = CpuDiente::where('id_paciente', $request->atencion['id_persona'])->first();

            if ($cpuDiente) {
                // Si ya existe, actualizar el registro
                $cpuDiente->update([
                    'arcada' => $arcada, // Guardar la estructura de arcada como un array
                    
                ]);
            } else {
                // Si no existe, crear un nuevo registro de dientes
                $cpuDiente = CpuDiente::create([
                    'id_paciente' => $request->atencion['id_persona'],
                    'arcada' => $arcada, // Guardar la estructura de arcada como un array
                ]);
            }
            // Crear la atención odontológica específica
            $atencionOdontologica = CpuAtencionOdontologia::create([
                'id_cpu_atencion' => $atencion->id,
                'id_diente' => $cpuDiente->id, // Relacionar el diente existente o creado
                'enfermedad_actual' => $request->atencion['enfermedad_proble_actual'],
                'examenes_estomatognatico' => !empty($request->examen_estomatognatico) ? json_encode($request->examen_estomatognatico) : null,
                'planes' => !empty($request->planes) ? json_encode($request->planes) : null,
                'tratamiento' => !empty($request->tratamientos) ? json_encode($request->tratamientos) : null,
            ]);
            // Guardar datos de derivación si el switch de derivación está activo
            if ($request->input('atencion.derivacionActive')) {
                $derivacion = $request->input('derivacion');
                // Validar los datos de la derivación
                $derivacionData = Validator::make($derivacion, [
                    'id_doctor_al_que_derivan' => 'required|integer|exists:users,id',
                    'id_paciente' => 'required|integer|exists:cpu_personas,id',
                    'motivo_derivacion' => 'required|string',
                    'id_area' => 'required|integer',
                    'fecha_para_atencion' => 'required|date',
                    'hora_para_atencion' => 'required|date_format:H:i:s',
                    'id_estado_derivacion' => 'integer|exists:cpu_estados,id',
                    'id_turno_asignado' => 'required|integer|exists:cpu_turnos,id_turnos',
                ])->validate();
            
                $derivacionData['ate_id'] = $atencion->id; // Aquí usas $atencion correctamente
                $derivacionData['id_funcionario_que_derivo'] = Auth::id();
                $derivacionData['fecha_derivacion'] = Carbon::now();
                $derivacion = CpuDerivacion::create($derivacionData);
            
                // Actualizar el estado del turno si la derivación es exitosa
                CpuTurno::where('id_turnos', $derivacionData['id_turno_asignado'])
                    ->update(['estado' => 7]);
            }
            
            DB::commit();
            return response()->json(['message' => 'Atención guardada con éxito'], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al guardar la atención odontológica',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ], 500);
        }
    }
}
