<?php

namespace App\Http\Controllers;

use App\Models\CpuAtencion;
use App\Models\CpuAtencionesDiversidad;
use App\Models\CpuAtencionesDivBeneficios;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

class AtencionesDiversidadController extends Controller
{
    /* ======================= Helpers ======================= */

    /** Convierte varias formas ("SI", "sí", true, "1") a bool o null */
    private function toBoolOrNull($v)
    {
        if ($v === null) return null;
        $s = is_string($v) ? mb_strtolower(trim($v)) : $v;

        if ($s === true || $s === 1 || $s === '1') return true;
        if ($s === false || $s === 0 || $s === '0') return false;

        if (is_string($s)) {
            if (in_array($s, ['si', 'sí', 'true', 'on', 'yes', 'y'], true)) return true;
            if (in_array($s, ['no', 'false', 'off', 'not', 'n'], true)) return false;
        }
        return null;
    }

    /** Entero o null */
    private function toIntOrNull($v)
    {
        if ($v === null || $v === '') return null;
        $n = filter_var($v, FILTER_VALIDATE_INT);
        return $n === false ? null : $n;
    }

    /** Devuelve fecha 'Y-m-d' o null, aceptando 'd/m/Y' o parse genérico */
    private function toDateYmdOrNull($v)
    {
        if (!$v) return null;
        if (is_string($v) && preg_match('#^\d{2}/\d{2}/\d{4}$#', $v)) {
            try {
                return Carbon::createFromFormat('d/m/Y', $v)->format('Y-m-d');
            } catch (\Throwable $e) {
                return null;
            }
        }
        // ISO u otros formatos aceptados por Carbon
        try {
            return Carbon::parse($v)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** Devuelve timestamp o now() si viene vacío; acepta ISO */
    private function toTimestampOrNow($v)
    {
        if (!$v) return now();
        try {
            return Carbon::parse($v);
        } catch (\Throwable $e) {
            return now();
        }
    }

    /** Toma $beneficios['detalle'] o $beneficios['items'] indistinto y lo retorna listo para jsonb */
    private function normalizeDetalleBeneficios($ben)
    {
        $detalle = $ben['detalle'] ?? ($ben['items'] ?? null);
        // Acepta array/obj; si viene array lo codificamos a JSON para jsonb
        if (is_array($detalle)) {
            return json_encode($detalle);
        }
        // Si ya viene como string JSON, lo dejamos tal cual; si viene null retorna null
        return $detalle;
    }

    /** "SI"/"NO"/true/false/1/0 -> "SI"|"NO"|null */
    private function toSiNoOrNull($v)
    {
        if ($v === null || $v === '') return null;
        if (is_bool($v)) return $v ? 'SI' : 'NO';
        $s = mb_strtolower(trim((string)$v));
        if (in_array($s, ['si', 'sí', '1', 'true', 'on', 'y', 'yes'], true)) return 'SI';
        if (in_array($s, ['no', '0', 'false', 'off', 'n'], true)) return 'NO';
        return null;
    }

    /** "SI"/"NO"/true/false/1/0 -> bool|null */
    private function toBoolOrNullFromSiNo($v)
    {
        if ($v === null || $v === '') return null;
        if (is_bool($v)) return $v;
        $s = mb_strtolower(trim((string)$v));
        if (in_array($s, ['si', 'sí', '1', 'true', 'on', 'y', 'yes'], true)) return true;
        if (in_array($s, ['no', '0', 'false', 'off', 'n'], true)) return false;
        return null;
    }

    /** Mapea datos_personales (del front) -> columnas de cpu_personas */
    private function mapDatosPersonalesToPersona(array $dp): array
    {
        return [
            // OJO: aquí usamos "genero" (texto). No tocamos "sexo" (char(1)) porque tu UI no lo maneja.
            'genero'                  => $dp['genero']                ?? null,
            'discapacidad'            => $this->toSiNoOrNull($dp['discapacidad'] ?? null), // guarda "SI"/"NO"
            'tipo_discapacidad'       => $this->toIntOrNull($dp['tipo_discapacidad'] ?? null),
            'porcentaje_discapacidad' => isset($dp['porcentaje_discapacidad']) && $dp['porcentaje_discapacidad'] !== ''
                ? (float)$dp['porcentaje_discapacidad']
                : null,
            'provincia'               => $dp['provincia']             ?? null,
            'ciudad'                  => $dp['ciudad']                ?? null,
            'parroquia'               => $dp['parroquia']             ?? null,
            'direccion'               => $dp['direccion']             ?? null,
            'celular'                 => $dp['celular']               ?? null,
            'ocupacion'               => $dp['ocupacion']             ?? null,
            'bono_desarrollo'         => $this->toSiNoOrNull($dp['bono_desarrollo'] ?? null), // "SI"/"NO"
            'carnet_conadis'          => $this->toBoolOrNullFromSiNo($dp['carnet_conadis'] ?? null), // bool
            'estado_civil'            => $dp['estado_civil']          ?? null,
            'nacionalidad'            => $dp['nacionalidad']          ?? null,
            'tipoetnia'               => $dp['tipoetnia']             ?? null,
            'updated_at'              => now(),
        ];
    }

    /** bool/si-no a bool|null */
    private function toBoolOrNullLoose($v)
    {
        if ($v === null || $v === '') return null;
        if (is_bool($v)) return $v;
        $s = mb_strtolower(trim((string)$v));
        if (in_array($s, ['si', 'sí', '1', 'true', 'on', 'y', 'yes'], true)) return true;
        if (in_array($s, ['no', '0', 'false', 'off', 'n'], true)) return false;
        return null;
    }

    /** numero o null (admite coma) */
    private function toNumOrNull($v)
    {
        if ($v === null || $v === '') return null;
        $s = str_replace(',', '.', (string)$v);
        return is_numeric($s) ? (float)$s : null;
    }

    /** jsonb: si llega array -> json_encode; si string JSON -> tal cual; null si vacío */
    private function toJsonbOrNull($v)
    {
        if ($v === null) return null;
        if (is_array($v)) return json_encode($v);
        $s = trim((string)$v);
        if ($s === '') return null;
        return $s; // asumimos que ya es JSON válido
    }

    /** Calcula IMC si no viene, con peso (kg) y talla (m) */
    private function calcImcOrNull($peso, $talla, $imc)
    {
        $p = $this->toNumOrNull($peso);
        $t = $this->toNumOrNull($talla);
        $i = $this->toNumOrNull($imc);
        if ($i !== null) return $i;
        if ($p !== null && $t !== null && $t > 0) {
            return round($p / ($t * $t), 2);
        }
        return null;
    }

    /** Mapea arreglo medico -> columnas de cpu_datos_medicos */
    private function mapMedicoToSaludRow(int $personaId, array $m): array
    {
        $peso  = $this->toNumOrNull($m['peso']  ?? null);
        $talla = $this->toNumOrNull($m['talla'] ?? null);
        $imc   = $this->calcImcOrNull($peso, $talla, $m['imc'] ?? null);

        return [
            'id_persona'                 => $personaId,
            'enfermedades_catastroficas' => $this->toBoolOrNullLoose($m['enfermedades_catastroficas'] ?? null) ?? false,
            'detalle_enfermedades'       => $this->toJsonbOrNull($m['detalle_enfermedades'] ?? null),
            'tipo_sangre'                => $this->toIntOrNull($m['tipo_sangre'] ?? null),
            'peso'                       => $peso,
            'talla'                      => $talla,
            'imc'                        => $imc,
            'alergias'                   => $m['alergias'] ?? null,
            'embarazada'                 => $this->toBoolOrNullLoose($m['embarazada'] ?? null) ?? false,
            'ultima_fecha_mestruacion'   => $this->toDateYmdOrNull($m['ultima_fecha_mestruacion'] ?? null),
            'semanas_embarazo'           => $this->toNumOrNull($m['semanas_embarazo'] ?? null),
            'fecha_estimada_parto'       => $this->toDateYmdOrNull($m['fecha_estimada_parto'] ?? null),
            'partos'                     => $this->toBoolOrNullLoose($m['partos'] ?? null) ?? false,
            'partos_data'                => $this->toJsonbOrNull($m['partos_data'] ?? null),
            'observacion_embarazo'       => $m['observacion_embarazo'] ?? null,
            'dependiente_medicamento'    => $this->toBoolOrNullLoose($m['dependiente_medicamento'] ?? null) ?? false,
            'medicamentos_dependiente'   => $this->toJsonbOrNull($m['medicamentos_dependiente'] ?? null),
            'tiene_seguro_medico'        => $this->toBoolOrNullLoose($m['tiene_seguro_medico'] ?? null) ?? false,
            'detalles_alergias'          => $this->toJsonbOrNull($m['detalles_alergias'] ?? null),
            'lactancia_inicio'           => $this->toDateYmdOrNull($m['lactancia_inicio'] ?? null),
            'lactancia_fin'              => $this->toDateYmdOrNull($m['lactancia_fin'] ?? null),
            'created_at'                 => now(),
            'updated_at'                 => now(),
        ];
    }



    /* ======================= End Helpers ======================= */


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
        // Validación principal (permite snapshot de diversidad + beneficios)
        $data = $request->validate([
            'id_funcionario'       => 'required|integer',
            'id_persona'           => 'required|integer',
            'via_atencion'         => 'nullable|string',
            'motivo_atencion'      => 'nullable|string',
            'fecha_hora_atencion'  => 'nullable|date',
            'detalle_atencion'     => 'nullable|string',

            // diversidad (snapshot)
            'diversidad'                                 => 'nullable|array',
            'diversidad.carrera'                         => 'nullable|string',
            'diversidad.status_academico'                => 'nullable|string',
            'diversidad.nivel_academico'                 => 'nullable|string',
            'diversidad.fecha_inicio_primer_nivel'       => 'nullable|string',
            'diversidad.fecha_ingreso_convalidacion'     => 'nullable|string',
            'diversidad.fecha_inicio_periodo'            => 'nullable|string',
            'diversidad.fecha_fin_periodo'               => 'nullable|string',
            'diversidad.segmentacion'                    => 'nullable|string',
            'diversidad.atencion_en_salud'               => 'nullable',
            'diversidad.lactancia'                       => 'nullable',
            'diversidad.numero_hijos'                    => 'nullable',
            'diversidad.hijos_con_discapacidad'          => 'nullable',
            'diversidad.enfermedad_catastrofica'         => 'nullable',
            'diversidad.adaptacion_curricular'           => 'nullable|string',
            'diversidad.acompanamiento_academico'        => 'nullable',
            'diversidad.tutor_asignado'                  => 'nullable|string',
            'diversidad.observacion'                     => 'nullable|string',

            // beneficios
            'beneficios'                 => 'nullable|array',
            'beneficios.recibe_incentivo' => 'nullable',
            'beneficios.recibe_credito'  => 'nullable',
            'beneficios.recibe_beca'     => 'nullable',
            'beneficios.anio_beca'       => 'nullable',
            'beneficios.detalle'         => 'nullable|array',
            // datos personales (opcionales)
            'datos_personales'                       => 'nullable|array',
            'datos_personales.genero'                => 'nullable|string',
            'datos_personales.discapacidad'          => 'nullable|string', // "SI"/"NO"/""
            'datos_personales.tipo_discapacidad'     => 'nullable|integer',
            'datos_personales.porcentaje_discapacidad' => 'nullable|numeric',
            'datos_personales.provincia'             => 'nullable|string',
            'datos_personales.ciudad'                => 'nullable|string',
            'datos_personales.parroquia'             => 'nullable|string',
            'datos_personales.direccion'             => 'nullable|string',
            'datos_personales.celular'               => 'nullable|string',
            'datos_personales.ocupacion'             => 'nullable|string',
            'datos_personales.bono_desarrollo'       => 'nullable|string', // "SI"/"NO"/""
            'datos_personales.carnet_conadis'        => 'nullable|string', // "SI"/"NO"/""
            'datos_personales.estado_civil'          => 'nullable|string',
            'datos_personales.nacionalidad'          => 'nullable|string',
            'datos_personales.tipoetnia'             => 'nullable|string',
            // bloque médico (opcional)
            'medico'                           => 'nullable|array',
            'medico.enfermedades_catastroficas' => 'nullable',
            'medico.detalle_enfermedades'      => 'nullable|array',
            'medico.tipo_sangre'               => 'nullable|integer',
            'medico.peso'                      => 'nullable|numeric',
            'medico.talla'                     => 'nullable|numeric',
            'medico.imc'                       => 'nullable|numeric',
            'medico.alergias'                  => 'nullable|string',
            'medico.embarazada'                => 'nullable',
            'medico.ultima_fecha_mestruacion'  => 'nullable|date',
            'medico.semanas_embarazo'          => 'nullable|numeric',
            'medico.fecha_estimada_parto'      => 'nullable|date',
            'medico.partos'                    => 'nullable',
            'medico.partos_data'               => 'nullable|array',
            'medico.observacion_embarazo'      => 'nullable|string',
            'medico.dependiente_medicamento'   => 'nullable',
            'medico.medicamentos_dependiente'  => 'nullable|array',
            'medico.tiene_seguro_medico'       => 'nullable',
            'medico.detalles_alergias'         => 'nullable|array',
            'medico.lactancia_inicio'          => 'nullable|date',
            'medico.lactancia_fin'             => 'nullable|date',
            'diversidad.dificultades_aprendizaje' => 'nullable|array', // múltiples
            'diversidad.dificultades_aprendizaje.*' => 'nullable|string', // items de texto

        ]);

        // También podemos leer 'medico' del request (aunque no esté en $data) para poblar flags relacionados
        $medico = $request->input('medico', []);

        return DB::transaction(function () use ($data, $medico) {
            $fh       = $this->toTimestampOrNow($data['fecha_hora_atencion'] ?? null);
            $anio     = (int)$fh->format('Y');

            $atencion = CpuAtencion::create([
                'id_funcionario'      => $data['id_funcionario'],
                'id_persona'          => $data['id_persona'],
                'via_atencion'        => $data['via_atencion'] ?? 'Presencial',
                'motivo_atencion'     => $data['motivo_atencion'] ?? 'Entrevista Atención a la Diversidad',
                'fecha_hora_atencion' => $fh,
                'anio_atencion'       => $anio,
                'detalle_atencion'    => $data['detalle_atencion'] ?? 'Corte histórico',
                'tipo_atencion'       => 'DIVERSIDAD',
                'id_estado'           => 1,
            ]);

            $div = $data['diversidad'] ?? [];

            $payloadDiv = [
                'id_atencion'                 => $atencion->id,
                'carrera'                     => $div['carrera'] ?? null,
                'status_academico'            => $div['status_academico'] ?? null,
                'nivel_academico'             => $div['nivel_academico'] ?? null,
                'fecha_inicio_primer_nivel'   => $this->toDateYmdOrNull($div['fecha_inicio_primer_nivel'] ?? null),
                'fecha_ingreso_convalidacion' => $this->toDateYmdOrNull($div['fecha_ingreso_convalidacion'] ?? null),
                'fecha_inicio_periodo'        => $this->toDateYmdOrNull($div['fecha_inicio_periodo'] ?? null),
                'fecha_fin_periodo'           => $this->toDateYmdOrNull($div['fecha_fin_periodo'] ?? null),
                'segmentacion'                => $div['segmentacion'] ?? null,
                'dificultades_aprendizaje' => $this->toJsonbOrNull($div['dificultades_aprendizaje'] ?? null),

                // booleans: aceptar SI/NO/true/false/1/0
                'atencion_en_salud'           => $this->toBoolOrNull($div['atencion_en_salud'] ?? null),
                'lactancia'                   => $this->toBoolOrNull(($div['lactancia'] ?? null) ?? ($medico['lactancia'] ?? null)),
                'enfermedad_catastrofica'     => $this->toBoolOrNull(($div['enfermedad_catastrofica'] ?? null) ?? ($medico['enfermedades_catastroficas'] ?? null)),
                'acompanamiento_academico'    => $this->toBoolOrNull($div['acompanamiento_academico'] ?? null),

                // ints
                'numero_hijos'                => $this->toIntOrNull($div['numero_hijos'] ?? null),
                'hijos_con_discapacidad'      => $this->toIntOrNull($div['hijos_con_discapacidad'] ?? null),

                'adaptacion_curricular'       => $div['adaptacion_curricular'] ?? null,
                'tutor_asignado'              => $div['tutor_asignado'] ?? null,
                'observacion'                 => $div['observacion'] ?? null,
            ];

            CpuAtencionesDiversidad::create($payloadDiv);

            $ben = $data['beneficios'] ?? [];
            $payloadBen = [
                'id_atencion'      => $atencion->id,
                'recibe_incentivo' => $this->toBoolOrNull($ben['recibe_incentivo'] ?? null),
                'recibe_credito'   => $this->toBoolOrNull($ben['recibe_credito'] ?? null),
                'recibe_beca'      => $this->toBoolOrNull($ben['recibe_beca'] ?? null),
                'anio_beca'        => $this->toIntOrNull($ben['anio_beca'] ?? null),
                'detalle'          => $this->normalizeDetalleBeneficios($ben),
            ];
            CpuAtencionesDivBeneficios::create($payloadBen);

            // === Actualizar cpu_personas si llegan datos_personales ===
            if (!empty($data['datos_personales']) && !empty($data['id_persona'])) {
                $personSet = $this->mapDatosPersonalesToPersona($data['datos_personales']);
                // Limpia nulls "no significativos" si lo prefieres (opcional)
                // $personSet = array_filter($personSet, fn($v) => $v !== null, ARRAY_FILTER_USE_BOTH);

                DB::table('cpu_personas')
                    ->where('id', (int)$data['id_persona'])
                    ->update($personSet);
            }

            if (!empty($medico)) {
                $row = $this->mapMedicoToSaludRow((int)$data['id_persona'] ?? (int)$atencion->id_persona, $medico);

                $last = DB::table('cpu_datos_medicos')
                    ->where('id_persona', (int)($data['id_persona'] ?? $atencion->id_persona))
                    ->orderByDesc('created_at')
                    ->first();

                if ($last) {
                    unset($row['created_at']); // preserva created_at del primero
                    DB::table('cpu_datos_medicos')->where('id', $last->id)->update($row);
                } else {
                    DB::table('cpu_datos_medicos')->insert($row);
                }
            }



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
            'via_atencion'        => 'nullable|string',
            'motivo_atencion'     => 'nullable|string',
            'fecha_hora_atencion' => 'nullable|date',
            'detalle_atencion'    => 'nullable|string',

            'diversidad'          => 'nullable|array',
            'beneficios'          => 'nullable|array',
        ]);

        // por si llegan datos de 'medico' que alimenten flags
        $medico = $request->input('medico', []);

        return DB::transaction(function () use ($data, $atencion, $medico) {
            if (isset($data['via_atencion']))       $atencion->via_atencion = $data['via_atencion'];
            if (isset($data['motivo_atencion']))    $atencion->motivo_atencion = $data['motivo_atencion'];
            if (isset($data['fecha_hora_atencion'])) {
                $fh = $this->toTimestampOrNow($data['fecha_hora_atencion']);
                $atencion->fecha_hora_atencion = $fh;
                $atencion->anio_atencion = (int)$fh->format('Y');
            }
            if (isset($data['detalle_atencion']))   $atencion->detalle_atencion = $data['detalle_atencion'];
            $atencion->save();

            if (isset($data['diversidad'])) {
                $div = $data['diversidad'];

                $payload = [
                    'carrera'                     => $div['carrera'] ?? null,
                    'status_academico'            => $div['status_academico'] ?? null,
                    'nivel_academico'             => $div['nivel_academico'] ?? null,
                    'segmentacion'                => $div['segmentacion'] ?? null,

                    'atencion_en_salud'           => $this->toBoolOrNull($div['atencion_en_salud'] ?? null),
                    'lactancia'                   => $this->toBoolOrNull(($div['lactancia'] ?? null) ?? ($medico['lactancia'] ?? null)),
                    'numero_hijos'                => $this->toIntOrNull($div['numero_hijos'] ?? null),
                    'hijos_con_discapacidad'      => $this->toIntOrNull($div['hijos_con_discapacidad'] ?? null),
                    'enfermedad_catastrofica'     => $this->toBoolOrNull(($div['enfermedad_catastrofica'] ?? null) ?? ($medico['enfermedades_catastroficas'] ?? null)),
                    'adaptacion_curricular'       => $div['adaptacion_curricular'] ?? null,
                    'acompanamiento_academico'    => $this->toBoolOrNull($div['acompanamiento_academico'] ?? null),
                    'tutor_asignado'              => $div['tutor_asignado'] ?? null,
                    'observacion'                 => $div['observacion'] ?? null,
                ];

                // Fechas flexibles
                foreach (
                    [
                        'fecha_inicio_primer_nivel'   => 'fecha_inicio_primer_nivel',
                        'fecha_ingreso_convalidacion' => 'fecha_ingreso_convalidacion',
                        'fecha_inicio_periodo'        => 'fecha_inicio_periodo',
                        'fecha_fin_periodo'           => 'fecha_fin_periodo',
                    ] as $in => $col
                ) {
                    if (array_key_exists($in, $div)) {
                        $payload[$col] = $this->toDateYmdOrNull($div[$in]);
                    }
                }

                $atencion->diversidad
                    ? $atencion->diversidad->update($payload)
                    : CpuAtencionesDiversidad::create(array_merge($payload, ['id_atencion' => $atencion->id]));
            }

            if (isset($data['beneficios'])) {
                $ben = $data['beneficios'];
                $payload = [
                    'recibe_incentivo' => $this->toBoolOrNull($ben['recibe_incentivo'] ?? null),
                    'recibe_credito'   => $this->toBoolOrNull($ben['recibe_credito'] ?? null),
                    'recibe_beca'      => $this->toBoolOrNull($ben['recibe_beca'] ?? null),
                    'anio_beca'        => $this->toIntOrNull($ben['anio_beca'] ?? null),
                    'detalle'          => $this->normalizeDetalleBeneficios($ben),
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

    /** GET /diversidad/prefetch?persona_id= */
    public function prefetch(Request $request)
    {
        $personaId = (int) $request->query('persona_id');
        if (!$personaId) return response()->json(['ok' => false, 'msg' => 'persona_id requerido'], 422);

        $row = DB::selectOne("SELECT public.f_div_modal_prefetch(?) AS payload", [$personaId]);
        $payload = $row && isset($row->payload) ? json_decode($row->payload, true) : [];
        return response()->json($payload);
    }

    /** POST /diversidad/salud (histórico) */
    public function actualizarSalud(Request $request)
    {
        $data = $request->validate([
            'persona_id' => 'required|integer',

            // Enfermedades catastróficas
            'enfermedades_catastroficas' => 'required|boolean',
            'detalle_enfermedades'       => 'nullable|array',

            // Embarazo
            'embarazada'               => 'nullable|boolean',
            'ultima_fecha_mestruacion' => 'nullable|date',    // YYYY-MM-DD
            'semanas_embarazo'         => 'nullable|numeric',
            'fecha_estimada_parto'     => 'nullable|date',
            'observacion_embarazo'     => 'nullable|string',

            // Lactancia
            'lactancia'         => 'nullable|boolean',
            'lactancia_inicio'  => 'nullable|date',
            'lactancia_fin'     => 'nullable|date',

            // Partos
            'partos'            => 'nullable|boolean',
            'partos_data'       => 'nullable|array',
        ]);
        $personaId = (int) $data['persona_id'];

        $payload = [
            'id_persona'                 => $personaId,
            'enfermedades_catastroficas' => $data['enfermedades_catastroficas'],
            'detalle_enfermedades'       => isset($data['detalle_enfermedades']) ? json_encode($data['detalle_enfermedades']) : null,

            'embarazada'                 => $data['embarazada'] ?? false,
            'ultima_fecha_mestruacion'   => $data['ultima_fecha_mestruacion'] ?? null,
            'semanas_embarazo'           => $data['semanas_embarazo'] ?? null,
            'fecha_estimada_parto'       => $data['fecha_estimada_parto'] ?? null,
            'observacion_embarazo'       => $data['observacion_embarazo'] ?? null,

            'lactancia_inicio'           => $data['lactancia_inicio'] ?? null,
            'lactancia_fin'              => $data['lactancia_fin'] ?? null,

            'partos'                     => $data['partos'] ?? false,
            'partos_data'                => isset($data['partos_data']) ? json_encode($data['partos_data']) : null,

            'created_at'                 => now(),
            'updated_at'                 => now(),
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
     * Devuelve { carrera: string|null } tomando el último registro
     */
    public function ultimaCarreraDePersona($personaId)
    {
        $personaId = (int)$personaId;

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

    /** GET /diversidad/segmento?cedula= */
    public function resolverSegmentoPorCedula(Request $request)
    {
        $cedula = trim((string)$request->query('cedula', ''));
        if ($cedula === '') {
            return response()->json(['segmento' => null, 'source' => null]);
        }

        // 1) PERSONA -> ID por cedula
        $personaId = DB::table('cpu_personas')
            ->where('cedula', $cedula)
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
                return response()->json(['segmento' => trim($segEst), 'source' => 'persona']);
            }
        } else {
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

        // 3) LEGALIZACIÓN
        if (Schema::hasTable('cpu_legalizacion_matricula')) {
            $q = DB::table('cpu_legalizacion_matricula')->where('cedula', $cedula);
            $segLegal = Schema::hasColumn('cpu_legalizacion_matricula', 'segmento_persona')
                ? $q->orderByDesc(DB::raw('COALESCE(updated_at, created_at, now())'))
                ->orderByDesc('id')
                ->value('segmento_persona')
                : null;

            if ($segLegal && trim($segLegal) !== '') {
                return response()->json(['segmento' => trim($segLegal), 'source' => 'legalizacion']);
            }
        }

        // 4) MTN
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
     * body: { segmento: string }
     */
    public function actualizarSegmentoPersona(Request $request, $personaId)
    {
        $personaId = (int)$personaId;
        $data = $request->validate([
            'segmento' => 'nullable|string'
        ]);
        $segmento = isset($data['segmento']) ? trim((string)$data['segmento']) : null;

        $row = DB::table('cpu_datos_estudiantes')
            ->where('id_persona', $personaId)
            ->orderByDesc(DB::raw('COALESCE(updated_at, created_at, now())'))
            ->orderByDesc('id')
            ->first();

        if ($row) {
            $updated = DB::table('cpu_datos_estudiantes')
                ->where('id', $row->id)
                ->update([
                    'segmentacion_persona' => $segmento ?: null,
                    'updated_at' => now(),
                ]);
            return response()->json(['ok' => (bool)$updated]);
        } else {
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
