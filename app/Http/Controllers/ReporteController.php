<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReporteController extends Controller
{
    //reporte para obtener valores únicos de cada campo requerido
    public function getAllUnifiedUniqueValuesForSelects()
    {
        // Función para normalizar cadenas: convierte a mayúsculas, quita espacios, elimina caracteres especiales y normaliza variaciones.
        $normalize = function ($value) {
            if (is_null($value)) {
                return null;
            }
            $value = mb_strtoupper(trim($value)); // Convertir a mayúsculas y quitar espacios
            $value = str_replace(['Á', 'É', 'Í', 'Ó', 'Ú'], ['A', 'E', 'I', 'O', 'U'], $value); // Normaliza caracteres con tilde
            $value = preg_replace('/(\/A|\/O)$/', '', $value); // Elimina sufijos como "/A" o "/O"
            return $value;
        };

        // Función para convertir valores booleanos a "SI" o "NO"
        $booleanToSiNo = function ($value) {
            return $value ? 'SI' : 'NO';
        };

        // Obtener valores únicos de cpu_personas
        $uniqueProvincias = DB::table('cpu_personas')
            ->select('provincia')
            ->distinct()
            ->get()
            ->pluck('provincia')
            ->filter()
            ->map(fn($provincia) => $normalize($provincia))
            ->unique()
            ->sort()
            ->values();

        $uniqueCiudades = DB::table('cpu_personas')
            ->select('ciudad')
            ->distinct()
            ->get()
            ->pluck('ciudad')
            ->filter()
            ->map(fn($ciudad) => $normalize($ciudad))
            ->unique()
            ->sort()
            ->values();

        $uniqueSexos = DB::table('cpu_personas')
            ->select('sexo')
            ->distinct()
            ->get()
            ->pluck('sexo')
            ->filter()
            ->map(fn($sexo) => $normalize($sexo))
            ->unique()
            ->sort()
            ->values();

        $uniqueTipoEtnia = DB::table('cpu_personas')
            ->select('tipoetnia')
            ->distinct()
            ->get()
            ->pluck('tipoetnia')
            ->filter()
            ->map(fn($tipoetnia) => $normalize($tipoetnia))
            ->unique()
            ->sort()
            ->values();

        $uniqueDiscapacidades = DB::table('cpu_personas')
            ->select('discapacidad')
            ->distinct()
            ->get()
            ->pluck('discapacidad')
            ->filter()
            ->map(fn($discapacidad) => $normalize($discapacidad))
            ->unique()
            ->sort()
            ->values();

        // Relacionar clasificacionUsuarios con cpu_tipo_usuario y obtener tipo_usuario
        $uniqueClasificacionUsuarios = DB::table('cpu_personas')
            ->select('id_clasificacion_tipo_usuario')
            ->distinct()
            ->get()
            ->pluck('id_clasificacion_tipo_usuario')
            ->filter()
            ->unique()
            ->map(function ($clasificacion) {
                $tipoUsuario = DB::table('cpu_tipo_usuario')
                    ->where('clasificacion', $clasificacion)
                    ->value('tipo_usuario');
                return [
                    'clasificacion' => $clasificacion,
                    'tipo_usuario' => $tipoUsuario
                ];
            })
            ->sortBy('clasificacion')
            ->values();

        // Obtener valores únicos de cpu_datos_estudiantes y cpu_datos_empleados para unificación de carrera y unidad/facultad
        $carrerasEstudiantes = DB::table('cpu_datos_estudiantes')
            ->select('carrera')
            ->distinct()
            ->get()
            ->pluck('carrera')
            ->filter()
            ->map(fn($carrera) => $normalize($carrera))
            ->unique();

        $carrerasEmpleados = DB::table('cpu_datos_empleados')
            ->select('carrera')
            ->distinct()
            ->get()
            ->pluck('carrera')
            ->filter()
            ->map(fn($carrera) => $normalize($carrera))
            ->unique();

        // Combinar carreras y asegurar valores únicos
        $unifiedCarreras = $carrerasEstudiantes->merge($carrerasEmpleados)->unique()->sort()->values();

        $unidadesEstudiantes = DB::table('cpu_datos_estudiantes')
            ->select('facultad')
            ->distinct()
            ->get()
            ->pluck('facultad')
            ->filter()
            ->map(fn($facultad) => $normalize($facultad))
            ->unique();

        $unidadesEmpleados = DB::table('cpu_datos_empleados')
            ->select('unidad')
            ->distinct()
            ->get()
            ->pluck('unidad')
            ->filter()
            ->map(fn($unidad) => $normalize($unidad))
            ->unique();

        // Combinar unidades/facultades y asegurar valores únicos
        $unifiedUnidades = $unidadesEstudiantes->merge($unidadesEmpleados)->unique()->sort()->values();

        // Obtener otros valores únicos de cpu_datos_estudiantes
        $uniqueCampus = DB::table('cpu_datos_estudiantes')
            ->select('campus')
            ->distinct()
            ->get()
            ->pluck('campus')
            ->filter()
            ->map(fn($campus) => $normalize($campus))
            ->unique()
            ->sort()
            ->values();

        $uniqueEstadoCivil = DB::table('cpu_datos_estudiantes')
            ->select('estado_civil')
            ->distinct()
            ->get()
            ->pluck('estado_civil')
            ->filter()
            ->map(fn($estado) => $normalize($estado))
            ->unique()
            ->sort()
            ->values();

        $uniqueSegmentacionPersona = DB::table('cpu_datos_estudiantes')
            ->select('segmentacion_persona')
            ->distinct()
            ->get()
            ->pluck('segmentacion_persona')
            ->filter()
            ->map(fn($segmentacion) => $normalize($segmentacion))
            ->unique()
            ->sort()
            ->values();

        // Obtener valores únicos de cpu_datos_medicos y convertir booleanos a "SI"/"NO"
        $uniqueEnfermedadesCatastroficas = DB::table('cpu_datos_medicos')
            ->select('enfermedades_catastroficas')
            ->distinct()
            ->get()
            ->pluck('enfermedades_catastroficas')
            ->filter()
            ->map(fn($value) => $booleanToSiNo($value))
            ->unique()
            ->values();

        $uniqueTieneSeguroMedico = DB::table('cpu_datos_medicos')
            ->select('tiene_seguro_medico')
            ->distinct()
            ->get()
            ->pluck('tiene_seguro_medico')
            ->filter()
            ->map(fn($value) => $booleanToSiNo($value))
            ->unique()
            ->values();

        $uniqueEmbarazada = DB::table('cpu_datos_medicos')
            ->select('embarazada')
            ->distinct()
            ->get()
            ->pluck('embarazada')
            ->filter()
            ->map(fn($value) => $booleanToSiNo($value))
            ->unique()
            ->values();

        // Obtener valores únicos de cpu_datos_empleados
        $uniquePuesto = DB::table('cpu_datos_empleados')
            ->select('puesto')
            ->distinct()
            ->get()
            ->pluck('puesto')
            ->filter()
            ->map(fn($puesto) => $normalize($puesto))
            ->unique()
            ->sort()
            ->values();

        $uniqueModalidad = DB::table('cpu_datos_empleados')
            ->select('modalidad')
            ->distinct()
            ->get()
            ->pluck('modalidad')
            ->filter()
            ->map(fn($modalidad) => $normalize($modalidad))
            ->unique()
            ->sort()
            ->values();

        //auditar
        $this->auditar('reporte', 'getAllUnifiedUniqueValuesForSelects', '', '', 'CONSULTA', 'Consulta de valores únicos para selects para reportes');

        // Retorna todos los valores normalizados, unificados y únicos
        return response()->json([
            'provincias' => $uniqueProvincias,
            'ciudades' => $uniqueCiudades,
            'sexos' => $uniqueSexos,
            'tipoetnia' => $uniqueTipoEtnia,
            'discapacidades' => $uniqueDiscapacidades,
            'clasificacionUsuarios' => $uniqueClasificacionUsuarios,
            'campus' => $uniqueCampus,
            'estado_civil' => $uniqueEstadoCivil,
            'segmentacion_persona' => $uniqueSegmentacionPersona,
            'enfermedades_catastroficas' => $uniqueEnfermedadesCatastroficas,
            'tiene_seguro_medico' => $uniqueTieneSeguroMedico,
            'embarazada' => $uniqueEmbarazada,
            'puesto' => $uniquePuesto,
            'modalidad' => $uniqueModalidad,
            'facultades' => $unifiedUnidades,
            'carreras' => $unifiedCarreras,
        ]);
    }

    // Función para obtener el total de registros de atenciones en un rango de fechas
    public function getTotalAtencionesPorFecha(Request $request)
    {
        $fechaInicio = $request->input('fechaInicio');
        $fechaFin = $request->input('fechaFin');

        // Función para normalizar cadenas
        $normalize = function ($value) {
            if (is_null($value)) {
                return null;
            }
            $value = mb_strtoupper(trim($value)); // Convertir a mayúsculas y quitar espacios
            $value = str_replace(['Á', 'É', 'Í', 'Ó', 'Ú'], ['A', 'E', 'I', 'O', 'U'], $value); // Normaliza caracteres con tilde
            return preg_replace('/(\/A|\/O)$/', '', $value); // Elimina sufijos como "/A" o "/O"
        };

        // Función para convertir valores booleanos a "SI" o "NO"
        $booleanToSiNo = function ($value) {
            return $value ? 'SI' : 'NO';
        };

        // Obtener las atenciones dentro del rango de fechas
        $atenciones = DB::table('cpu_atenciones as at')
            ->join('cpu_personas as p', 'at.id_persona', '=', 'p.id')
            ->leftJoin('cpu_datos_estudiantes as de', 'p.id', '=', 'de.id_persona')
            ->leftJoin('cpu_datos_empleados as dem', 'p.id', '=', 'dem.id_persona')
            ->leftJoin('cpu_datos_medicos as dm', 'p.id', '=', 'dm.id_persona')
            ->select(
                'at.id',
                'at.id_persona',
                'at.via_atencion',
                'at.motivo_atencion',
                'at.fecha_hora_atencion',
                'at.detalle_atencion',
                'at.tipo_atencion',
                'at.recomendacion',
                'p.cedula',
                'p.nombres',
                'p.provincia',
                'p.ciudad',
                'p.tipoetnia',
                'p.discapacidad',
                'p.sexo',
                'p.direccion',
                'de.campus',
                'de.estado_civil',
                'de.segmentacion_persona',
                'dem.puesto',
                'dem.modalidad',
                'dem.unidad as facultad',
                'dem.carrera',
                'dm.enfermedades_catastroficas',
                'dm.tiene_seguro_medico',
                'dm.embarazada'
            )
            ->whereBetween('at.fecha_hora_atencion', [$fechaInicio, $fechaFin])
            ->get();

        // Agrupar por persona
        $agrupadoPorPersona = $atenciones->groupBy('id_persona')->map(function ($atenciones, $id_persona) use ($normalize, $booleanToSiNo) {
            $persona = $atenciones->first(); // Información principal de la persona
            return [
                'id_persona' => $id_persona,
                'cedula' => $persona->cedula,
                'nombres' => $normalize($persona->nombres),
                'provincia' => $normalize($persona->provincia),
                'ciudad' => $normalize($persona->ciudad),
                'sexo' => $normalize($persona->sexo),
                'tipoetnia' => $normalize($persona->tipoetnia),
                'discapacidad' => $booleanToSiNo($persona->discapacidad),
                'clasificacionUsuario' => $normalize($persona->tipoetnia), // Este campo puede adaptarse según la tabla
                'campus' => $normalize($persona->campus),
                'estadoCivil' => $normalize($persona->estado_civil),
                'segmentacionPersona' => $normalize($persona->segmentacion_persona),
                'enfermedadesCatastroficas' => $booleanToSiNo($persona->enfermedades_catastroficas),
                'tieneSeguroMedico' => $booleanToSiNo($persona->tiene_seguro_medico),
                'embarazada' => $booleanToSiNo($persona->embarazada),
                'puesto' => $normalize($persona->puesto),
                'modalidad' => $normalize($persona->modalidad),
                'facultad' => $normalize($persona->facultad),
                'carrera' => $normalize($persona->carrera),
                'totalAtenciones' => $atenciones->count(),
                'atenciones' => $atenciones->map(function ($atencion) use ($normalize) {
                    return [
                        'id' => $atencion->id,
                        'via_atencion' => $normalize($atencion->via_atencion),
                        'motivo_atencion' => $normalize($atencion->motivo_atencion),
                        'fecha_hora_atencion' => Carbon::parse($atencion->fecha_hora_atencion)->translatedFormat('l, d F Y'),
                        'detalle_atencion' => $normalize($atencion->detalle_atencion),
                        'tipo_atencion' => $normalize($atencion->tipo_atencion),
                        'recomendacion' => $normalize($atencion->recomendacion),
                    ];
                })
            ];
        })->values();

        //auditar
        $this->auditar('reporte', 'getTotalAtencionesPorFecha', '', '', 'CONSULTA', 'Consulta de total de atenciones por fecha');

        return response()->json([
            'total_atenciones' => $agrupadoPorPersona->sum(fn($persona) => $persona['totalAtenciones']), // Total de atenciones
            'personas' => $agrupadoPorPersona // Datos agrupados por persona
        ]);
    }

    //auditar
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
