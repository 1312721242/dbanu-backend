<?php

namespace App\Http\Controllers;

use App\Models\CpuAtencion;
use App\Models\CpuAtencionesDiversidad;
use App\Models\CpuAtencionesDivBeneficios;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;

class AtencionesDiversidadController extends Controller
{
    /** GET /diversidad/entrevistas?persona_id= */
    public function index(Request $request)
    {
        $personaId = $request->query('persona_id');

        $query = CpuAtencion::with(['diversidad', 'beneficios'])
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
        $at = CpuAtencion::with(['diversidad', 'beneficios'])->findOrFail($atencionId);
        return response()->json($at);
    }

    /** POST /diversidad/entrevistas */
    public function store(Request $request)
    {
        $data = $request->validate([
            'id_funcionario' => 'required|integer',
            'id_persona'     => 'required|integer',
            'via_atencion'   => 'nullable|string',
            'motivo_atencion' => 'nullable|string',
            'fecha_hora_atencion' => 'nullable|date', // o string
            'detalle_atencion' => 'nullable|string',

            // snapshot diversidad
            'diversidad.carrera'                  => 'nullable|string',
            'diversidad.status_academico'         => 'nullable|string',
            'diversidad.nivel_academico'          => 'nullable|string',
            'diversidad.fecha_inicio_primer_nivel' => 'nullable|string',
            'diversidad.fecha_ingreso_convalidacion' => 'nullable|string',
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
                'recibe_incentivo' => $ben['recibe_incentivo'] ?? null,
                'recibe_credito'  => $ben['recibe_credito'] ?? null,
                'recibe_beca'     => $ben['recibe_beca'] ?? null,
                'anio_beca'       => $ben['anio_beca'] ?? null,
                'detalle'         => $ben['detalle'] ?? null,
            ]);

            return response()->json(
                CpuAtencion::with(['diversidad', 'beneficios'])->find($atencion->id),
                201
            );
        });
    }

    /** PUT/PATCH /diversidad/entrevistas/{atencion_id} */
    public function update(Request $request, $atencionId)
    {
        $atencion = CpuAtencion::with(['diversidad', 'beneficios'])->findOrFail($atencionId);

        $data = $request->validate([
            'via_atencion'   => 'nullable|string',
            'motivo_atencion' => 'nullable|string',
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

                foreach (
                    [
                        'fecha_inicio_primer_nivel'   => 'fecha_inicio_primer_nivel',
                        'fecha_ingreso_convalidacion' => 'fecha_ingreso_convalidacion',
                        'fecha_inicio_periodo'        => 'fecha_inicio_periodo',
                        'fecha_fin_periodo'           => 'fecha_fin_periodo',
                    ] as $in => $col
                ) {
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
                CpuAtencion::with(['diversidad', 'beneficios'])->find($atencion->id)
            );
        });
    }

    public function prefetch(Request $request)
    {
        $personaId = (int) $request->query('persona_id');
        if (!$personaId) return response()->json(['ok' => false, 'msg' => 'persona_id requerido'], 422);

        $row = DB::selectOne("SELECT public.f_div_modal_prefetch(?) AS payload", [$personaId]);
        $payload = $row && isset($row->payload) ? json_decode($row->payload, true) : [];
        return response()->json($payload);
    }

    public function actualizarSalud(Request $request)
    {
        $data = $request->validate([
            'persona_id' => 'required|integer',

            // Enfermedades catastróficas
            'enfermedades_catastroficas' => 'required|boolean',
            'detalle_enfermedades'       => 'nullable|array',

            // Embarazo
            'embarazada'               => 'nullable|boolean',
            'ultima_fecha_mestruacion' => 'nullable|date',    // YYYY-MM-DD (tu columna es DATE)
            'semanas_embarazo'         => 'nullable|numeric',
            'fecha_estimada_parto'     => 'nullable|date',
            'observacion_embarazo'     => 'nullable|string',

            // Lactancia
            'lactancia'         => 'nullable|boolean', // (si lo necesitas para front; no se guarda)
            'lactancia_inicio'  => 'nullable|date',
            'lactancia_fin'     => 'nullable|date',

            // Partos
            'partos'            => 'nullable|boolean',
            'partos_data'       => 'nullable|array',
        ]);
        $personaId = (int) $data['persona_id'];

        $payload = [
            'id_persona'                => $personaId,
            'enfermedades_catastroficas' => $data['enfermedades_catastroficas'],
            'detalle_enfermedades'      => isset($data['detalle_enfermedades']) ? json_encode($data['detalle_enfermedades']) : null,

            'embarazada'                => $data['embarazada'] ?? false,
            'ultima_fecha_mestruacion'  => $data['ultima_fecha_mestruacion'] ?? null, // DATE
            'semanas_embarazo'          => $data['semanas_embarazo'] ?? null,
            'fecha_estimada_parto'      => $data['fecha_estimada_parto'] ?? null,
            'observacion_embarazo'      => $data['observacion_embarazo'] ?? null,

            'lactancia_inicio'          => $data['lactancia_inicio'] ?? null,
            'lactancia_fin'             => $data['lactancia_fin'] ?? null,

            'partos'                    => $data['partos'] ?? false,
            'partos_data'               => isset($data['partos_data']) ? json_encode($data['partos_data']) : null,

            'created_at'                => now(),
            'updated_at'                => now(),
        ];

        // SIEMPRE INSERTA (historial)
        DB::table('cpu_datos_medicos')->insert($payload);

        return response()->json(['ok' => true]);
    }


    /** GET /diversidad/carreras
     * Retorna string[] con valores únicos de cpu_datos_estudiantes.carrera
     */
    public function listarCarrerasDistinct()
    {
        $rows = DB::table('cpu_datos_estudiantes')
            ->select('carrera')
            ->whereNotNull('carrera')
            ->whereRaw("trim(carrera) <> ''")
            ->distinct()
            ->pluck('carrera')
            ->map(fn($s) => trim((string)$s))
            ->unique()
            ->sort()
            ->values()
            ->all();

        return response()->json($rows);
    }

    /** GET /diversidad/personas/{personaId}/ultima-carrera
     * Devuelve { carrera: string|null } tomando el último registro (created_at/updated_at/id)
     */
    public function ultimaCarreraDePersona($personaId)
    {
        $personaId = (int)$personaId;

        // Heurística: prioriza updated_at, luego created_at, luego id desc
        $row = DB::table('cpu_datos_estudiantes')
            ->where('id_persona', $personaId)
            ->whereNotNull('carrera')
            ->whereRaw("trim(carrera) <> ''")
            ->orderByDesc(DB::raw('COALESCE(updated_at, created_at, now())'))
            ->orderByDesc('id')
            ->select('carrera')
            ->first();

        return response()->json(['carrera' => $row->carrera ?? null]);
    }

    /** GET /diversidad/segmento?cedula=
     * Busca el segmento poblacional en:
     * 1) cpu_personas.pro_segmentacion_persona  (source='persona')
     * 2) cpu_legalizacion_matricula (ajusta nombre de columna si difiere) (source='legalizacion')
     * 3) (opcional) tabla MTN si existe, p.ej. cpu_mtn_personas.segmento (source='mtn')
     */
    public function resolverSegmentoPorCedula(Request $request)
    {
        $cedula = trim((string)$request->query('cedula', ''));
        if ($cedula === '') {
            return response()->json(['segmento' => null, 'source' => null]);
        }

        // 1) PERSONA -> ID por cedula
        $personaId = DB::table('cpu_personas')
            ->where('cedula', $cedula)   // tu tabla tiene 'cedula'
            ->value('id');

        // 2) Datos de estudiante (maestro que quieres usar)
        if ($personaId) {
            $segEst = DB::table('cpu_datos_estudiantes')
                ->where('id_persona', $personaId)
                ->whereNotNull('segmentacion_persona')
                ->whereRaw("trim(segmentacion_persona) <> ''")
                ->orderByDesc(DB::raw('COALESCE(updated_at, created_at, now())'))
                ->orderByDesc('id')
                ->value('segmentacion_persona');

            if ($segEst && trim($segEst) !== '') {
                // mantenemos 'persona' como source para que el front no vuelva a intentar actualizar
                return response()->json(['segmento' => trim($segEst), 'source' => 'persona']);
            }
        } else {
            // Join inverso por si no se encontró la persona por cualquier motivo
            $segJoin = DB::table('cpu_datos_estudiantes as de')
                ->join('cpu_personas as p', 'p.id', '=', 'de.id_persona')
                ->where('p.cedula', $cedula)
                ->whereNotNull('de.segmentacion_persona')
                ->whereRaw("trim(de.segmentacion_persona) <> ''")
                ->orderByDesc(DB::raw('COALESCE(de.updated_at, de.created_at, now())'))
                ->orderByDesc('de.id')
                ->value('de.segmentacion_persona');

            if ($segJoin && trim($segJoin) !== '') {
                return response()->json(['segmento' => trim($segJoin), 'source' => 'persona']);
            }
        }

        // 3) LEGALIZACIÓN: tu columna es 'segmento_persona'
        if (Schema::hasTable('cpu_legalizacion_matricula')) {
            // Evita columnas inexistentes en el WHERE
            $q = DB::table('cpu_legalizacion_matricula');
            $q->where(function ($w) use ($cedula) {
                $w->where('cedula', $cedula);
                // si en el futuro agregas otras, añádelas con hasColumn
            });

            $segLegal = Schema::hasColumn('cpu_legalizacion_matricula', 'segmento_persona')
                ? $q->orderByDesc(DB::raw('COALESCE(updated_at, created_at, now())'))
                ->orderByDesc('id')
                ->value('segmento_persona')
                : null;

            if ($segLegal && trim($segLegal) !== '') {
                return response()->json(['segmento' => trim($segLegal), 'source' => 'legalizacion']);
            }
        }

        // 4) MTN (tu tabla es cpu_mtn_2018_2022 con columnas 'cedula' y 'segmento')
        if (Schema::hasTable('cpu_mtn_2018_2022')) {
            $segMtn = DB::table('cpu_mtn_2018_2022')
                ->where('cedula', $cedula)
                ->orderByDesc('id')
                ->value('segmento');

            if ($segMtn && trim($segMtn) !== '') {
                return response()->json(['segmento' => trim($segMtn), 'source' => 'mtn']);
            }
        }

        return response()->json(['segmento' => null, 'source' => null]);
    }

    /** PUT /diversidad/personas/{personaId}/segmento
     * Actualiza cpu_personas.pro_segmentacion_persona
     * body: { segmento: string }
     */
    public function actualizarSegmentoPersona(Request $request, $personaId)
    {
        $personaId = (int)$personaId;
        $data = $request->validate([
            'segmento' => 'nullable|string'
        ]);
        $segmento = isset($data['segmento']) ? trim((string)$data['segmento']) : null;

        // Buscar el registro más reciente en cpu_datos_estudiantes
        $row = DB::table('cpu_datos_estudiantes')
            ->where('id_persona', $personaId)
            ->orderByDesc(DB::raw('COALESCE(updated_at, created_at, now())'))
            ->orderByDesc('id')
            ->first();

        if ($row) {
            // Actualiza el registro más reciente
            $updated = DB::table('cpu_datos_estudiantes')
                ->where('id', $row->id)
                ->update([
                    'segmentacion_persona' => $segmento ?: null,
                    'updated_at' => now(),
                ]);
            return response()->json(['ok' => (bool)$updated]);
        } else {
            // Si no hay fila para ese id_persona, crea una mínima
            DB::table('cpu_datos_estudiantes')->insert([
                'id_persona' => $personaId,
                'segmentacion_persona' => $segmento ?: null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            return response()->json(['ok' => true, 'created' => true]);
        }
    }
}
