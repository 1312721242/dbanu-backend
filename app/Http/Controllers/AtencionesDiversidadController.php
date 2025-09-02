<?php

namespace App\Http\Controllers;

use App\Models\CpuAtencion;
use App\Models\CpuAtencionesDiversidad;
use App\Models\CpuAtencionesDivBeneficios;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AtencionesDiversidadController extends Controller
{
    /** GET /diversidad/entrevistas?persona_id= */
    public function index(Request $request)
    {
        $personaId = $request->query('persona_id');

        $query = CpuAtencion::with(['diversidad','beneficios'])
            ->where('tipo_atencion', 'DIVERSIDAD')
            ->orderByDesc('fecha_hora_atencion');

        if ($personaId) {
            $query->where('id_persona', $personaId);
        }

        return response()->json($query->paginate(20));
    }

    /** GET /diversidad/entrevistas/{atencion_id} */
    public function show($atencionId)
    {
        $at = CpuAtencion::with(['diversidad','beneficios'])->findOrFail($atencionId);
        return response()->json($at);
    }

    /** POST /diversidad/entrevistas */
    public function store(Request $request)
    {
        $data = $request->validate([
            'id_funcionario' => 'required|integer',
            'id_persona'     => 'required|integer',
            'via_atencion'   => 'nullable|string',
            'motivo_atencion'=> 'nullable|string',
            'fecha_hora_atencion' => 'nullable|date', // o string
            'detalle_atencion' => 'nullable|string',

            // snapshot diversidad
            'diversidad.carrera'                  => 'nullable|string',
            'diversidad.status_academico'         => 'nullable|string',
            'diversidad.nivel_academico'          => 'nullable|string',
            'diversidad.fecha_inicio_primer_nivel'=> 'nullable|string',
            'diversidad.fecha_ingreso_convalidacion'=> 'nullable|string',
            'diversidad.fecha_inicio_periodo'     => 'nullable|string',
            'diversidad.fecha_fin_periodo'        => 'nullable|string',
            'diversidad.segmentacion'             => 'nullable|string',
            'diversidad.atencion_en_salud'        => 'nullable|boolean',
            'diversidad.lactancia'                => 'nullable|boolean',
            'diversidad.numero_hijos'             => 'nullable|integer',
            'diversidad.hijos_con_discapacidad'   => 'nullable|integer',
            'diversidad.enfermedad_catastrofica'  => 'nullable|boolean',
            'diversidad.adaptacion_curricular'    => 'nullable|string',
            'diversidad.acompanamiento_academico' => 'nullable|boolean',
            'diversidad.tutor_asignado'           => 'nullable|string',
            'diversidad.observacion'              => 'nullable|string',

            // beneficios
            'beneficios.recibe_incentivo' => 'nullable|boolean',
            'beneficios.recibe_credito'   => 'nullable|boolean',
            'beneficios.recibe_beca'      => 'nullable|boolean',
            'beneficios.anio_beca'        => 'nullable|integer',
            'beneficios.detalle'          => 'nullable|array',
        ]);

        return DB::transaction(function () use ($data) {
            $fh = $data['fecha_hora_atencion'] ?? now();
            $atencion = CpuAtencion::create([
                'id_funcionario'      => $data['id_funcionario'],
                'id_persona'          => $data['id_persona'],
                'via_atencion'        => $data['via_atencion'] ?? 'Presencial',
                'motivo_atencion'     => $data['motivo_atencion'] ?? 'Entrevista Atención a la Diversidad',
                'fecha_hora_atencion' => $fh,
                'anio_atencion'       => (int) Carbon::parse($fh)->format('Y'),
                'detalle_atencion'    => $data['detalle_atencion'] ?? 'Corte histórico',
                'tipo_atencion'       => 'DIVERSIDAD',
                'id_estado'           => 1,
            ]);

            $div = $data['diversidad'] ?? [];
            // Parsear fechas DD/MM/YYYY si vienen así
            $date = fn($s) => $s ? Carbon::createFromFormat('d/m/Y', $s)->format('Y-m-d') : null;

            CpuAtencionesDiversidad::create([
                'id_atencion'                 => $atencion->id,
                'carrera'                     => $div['carrera'] ?? null,
                'status_academico'            => $div['status_academico'] ?? null,
                'nivel_academico'             => $div['nivel_academico'] ?? null,
                'fecha_inicio_primer_nivel'   => isset($div['fecha_inicio_primer_nivel']) ? $date($div['fecha_inicio_primer_nivel']) : null,
                'fecha_ingreso_convalidacion' => isset($div['fecha_ingreso_convalidacion']) ? $date($div['fecha_ingreso_convalidacion']) : null,
                'fecha_inicio_periodo'        => isset($div['fecha_inicio_periodo']) ? $date($div['fecha_inicio_periodo']) : null,
                'fecha_fin_periodo'           => isset($div['fecha_fin_periodo']) ? $date($div['fecha_fin_periodo']) : null,
                'segmentacion'                => $div['segmentacion'] ?? null,
                'atencion_en_salud'           => $div['atencion_en_salud'] ?? null,
                'lactancia'                   => $div['lactancia'] ?? null,
                'numero_hijos'                => $div['numero_hijos'] ?? null,
                'hijos_con_discapacidad'      => $div['hijos_con_discapacidad'] ?? null,
                'enfermedad_catastrofica'     => $div['enfermedad_catastrofica'] ?? null,
                'adaptacion_curricular'       => $div['adaptacion_curricular'] ?? null,
                'acompanamiento_academico'    => $div['acompanamiento_academico'] ?? null,
                'tutor_asignado'              => $div['tutor_asignado'] ?? null,
                'observacion'                 => $div['observacion'] ?? null,
            ]);

            $ben = $data['beneficios'] ?? [];
            CpuAtencionesDivBeneficios::create([
                'id_atencion'     => $atencion->id,
                'recibe_incentivo'=> $ben['recibe_incentivo'] ?? null,
                'recibe_credito'  => $ben['recibe_credito'] ?? null,
                'recibe_beca'     => $ben['recibe_beca'] ?? null,
                'anio_beca'       => $ben['anio_beca'] ?? null,
                'detalle'         => $ben['detalle'] ?? null,
            ]);

            return response()->json(
                CpuAtencion::with(['diversidad','beneficios'])->find($atencion->id),
                201
            );
        });
    }

    /** PUT/PATCH /diversidad/entrevistas/{atencion_id} */
    public function update(Request $request, $atencionId)
    {
        $atencion = CpuAtencion::with(['diversidad','beneficios'])->findOrFail($atencionId);

        $data = $request->validate([
            'via_atencion'   => 'nullable|string',
            'motivo_atencion'=> 'nullable|string',
            'fecha_hora_atencion' => 'nullable|date',
            'detalle_atencion' => 'nullable|string',

            'diversidad' => 'nullable|array',
            'beneficios' => 'nullable|array',
        ]);

        return DB::transaction(function () use ($data, $atencion) {
            if (isset($data['via_atencion']))       $atencion->via_atencion = $data['via_atencion'];
            if (isset($data['motivo_atencion']))    $atencion->motivo_atencion = $data['motivo_atencion'];
            if (isset($data['fecha_hora_atencion'])) {
                $atencion->fecha_hora_atencion = $data['fecha_hora_atencion'];
                $atencion->anio_atencion = (int) Carbon::parse($data['fecha_hora_atencion'])->format('Y');
            }
            if (isset($data['detalle_atencion']))   $atencion->detalle_atencion = $data['detalle_atencion'];
            $atencion->save();

            if (isset($data['diversidad'])) {
                $div = $data['diversidad'];
                $date = fn($s) => $s ? Carbon::createFromFormat('d/m/Y', $s)->format('Y-m-d') : null;

                $payload = [
                    'carrera'                     => $div['carrera'] ?? null,
                    'status_academico'            => $div['status_academico'] ?? null,
                    'nivel_academico'             => $div['nivel_academico'] ?? null,
                    'segmentacion'                => $div['segmentacion'] ?? null,
                    'atencion_en_salud'           => $div['atencion_en_salud'] ?? null,
                    'lactancia'                   => $div['lactancia'] ?? null,
                    'numero_hijos'                => $div['numero_hijos'] ?? null,
                    'hijos_con_discapacidad'      => $div['hijos_con_discapacidad'] ?? null,
                    'enfermedad_catastrofica'     => $div['enfermedad_catastrofica'] ?? null,
                    'adaptacion_curricular'       => $div['adaptacion_curricular'] ?? null,
                    'acompanamiento_academico'    => $div['acompanamiento_academico'] ?? null,
                    'tutor_asignado'              => $div['tutor_asignado'] ?? null,
                    'observacion'                 => $div['observacion'] ?? null,
                ];

                foreach ([
                    'fecha_inicio_primer_nivel'   => 'fecha_inicio_primer_nivel',
                    'fecha_ingreso_convalidacion' => 'fecha_ingreso_convalidacion',
                    'fecha_inicio_periodo'        => 'fecha_inicio_periodo',
                    'fecha_fin_periodo'           => 'fecha_fin_periodo',
                ] as $in => $col) {
                    if (array_key_exists($in, $div)) {
                        $payload[$col] = $div[$in] ? $date($div[$in]) : null;
                    }
                }

                $atencion->diversidad
                    ? $atencion->diversidad->update($payload)
                    : CpuAtencionesDiversidad::create(array_merge($payload, ['id_atencion' => $atencion->id]));
            }

            if (isset($data['beneficios'])) {
                $ben = $data['beneficios'];
                $payload = [
                    'recibe_incentivo' => $ben['recibe_incentivo'] ?? null,
                    'recibe_credito'   => $ben['recibe_credito'] ?? null,
                    'recibe_beca'      => $ben['recibe_beca'] ?? null,
                    'anio_beca'        => $ben['anio_beca'] ?? null,
                    'detalle'          => $ben['detalle'] ?? null,
                ];
                $atencion->beneficios
                    ? $atencion->beneficios->update($payload)
                    : CpuAtencionesDivBeneficios::create(array_merge($payload, ['id_atencion' => $atencion->id]));
            }

            return response()->json(
                CpuAtencion::with(['diversidad','beneficios'])->find($atencion->id)
            );
        });
    }
}
