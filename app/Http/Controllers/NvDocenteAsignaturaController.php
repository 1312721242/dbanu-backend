<?php

namespace App\Http\Controllers;

use App\Models\NvDocenteAsignatura;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class NvDocenteAsignaturaController extends Controller
{
    private $auditoria;

    public function __construct()
    {
        $this->middleware('auth:api');
        $this->auditoria = new AuditoriaControllers();
    }

    // Listado con filtros (id_periodo, id_docente, id_asignatura, id_paralelo, activo)
    public function index(Request $r)
    {
        $q = DB::table('nv.docente_asignatura as da')
            ->join('nv.docentes as d','d.id','=','da.id_docente')
            ->join('nv.asignaturas as a','a.id','=','da.id_asignatura')
            ->join('nv.periodos as p','p.id','=','da.id_periodo')
            ->leftJoin('public.cpu_periodo as cp','cp.id','=','p.id_cpu_periodo')
            ->leftJoin('nv.paralelos as pa','pa.id','=','da.id_paralelo')
            ->select(
                'da.id','da.horas','da.activo',
                'd.id as id_docente','d.nombres','d.apellidos','d.identificacion',
                'a.id as id_asignatura','a.codigo as asig_codigo','a.nombre as asig_nombre',
                'p.id as id_periodo','p.nombre as nv_periodo','cp.nombre as cpu_periodo',
                'pa.id as id_paralelo','pa.codigo as paralelo'
            );

        if ($r->filled('id_periodo'))    $q->where('da.id_periodo', (int)$r->id_periodo);
        if ($r->filled('id_docente'))    $q->where('da.id_docente', (int)$r->id_docente);
        if ($r->filled('id_asignatura')) $q->where('da.id_asignatura', (int)$r->id_asignatura);
        if ($r->filled('id_paralelo'))   $q->where('da.id_paralelo', (int)$r->id_paralelo);
        if ($r->filled('activo'))        $q->where('da.activo', $r->boolean('activo'));

        $data = $q->orderBy('apellidos')->orderBy('nombres')->get();

        // Auditoría de consulta
        $this->auditoria->auditar(
            'nv.docente_asignatura','id','','','GET',
            'CONSULTA DOCENTE-ASIGNATURA', $r
        );

        return response()->json($data);
    }

    // Crear una asignación
    public function store(Request $r)
    {
        $v = Validator::make($r->all(),[
            'id_docente'    => 'required|integer|exists:nv.docentes,id',
            'id_asignatura' => 'required|integer|exists:nv.asignaturas,id',
            'id_periodo'    => 'required|integer|exists:nv.periodos,id',
            'id_paralelo'   => 'nullable|integer|exists:nv.paralelos,id',
            'horas'         => 'nullable|integer|min:0',
            'activo'        => 'boolean'
        ])->validate();

        // validación de unicidad (mismo criterio que índices únicos)
        $exists = NvDocenteAsignatura::query()
            ->when(isset($v['id_paralelo']), fn($q)=>$q->where('id_paralelo',$v['id_paralelo']))
            ->when(!isset($v['id_paralelo']), fn($q)=>$q->whereNull('id_paralelo'))
            ->where('id_docente',$v['id_docente'])
            ->where('id_asignatura',$v['id_asignatura'])
            ->where('id_periodo',$v['id_periodo'])
            ->exists();

        if ($exists) {
            return response()->json(['error'=>'Asignación duplicada para ese periodo/paralelo'], 422);
        }

        $row = NvDocenteAsignatura::create($v + ['activo'=>$v['activo'] ?? true]);

        $this->auditoria->auditar(
            'nv.docente_asignatura', 'id', '', (string)$row->id, 'INSERT',
            "CREA DOC-ASI: D{$row->id_docente} A{$row->id_asignatura} P{$row->id_periodo} PA".($row->id_paralelo??'NULL'),
            $r
        );

        return response()->json($row,201);
    }

    // Carga masiva (array de objetos)
    public function bulkUpsert(Request $request)
    {
        $rows = $request->input('asignaciones',[]);
        if (!is_array($rows) || empty($rows)) {
            return response()->json(['error'=>'Vacío o formato inválido'],422);
        }

        DB::beginTransaction();
        try{
            foreach($rows as $r){
                $v = Validator::make($r,[
                    'id_docente'    => 'required|integer|exists:nv.docentes,id',
                    'id_asignatura' => 'required|integer|exists:nv.asignaturas,id',
                    'id_periodo'    => 'required|integer|exists:nv.periodos,id',
                    'id_paralelo'   => 'nullable|integer|exists:nv.paralelos,id',
                    'horas'         => 'nullable|integer|min:0',
                    'activo'        => 'boolean'
                ])->validate();

                // Clave compuesta
                $where = [
                    'id_docente'   => $v['id_docente'],
                    'id_asignatura'=> $v['id_asignatura'],
                    'id_periodo'   => $v['id_periodo'],
                    'id_paralelo'  => $v['id_paralelo'] ?? null,
                ];

                $row = NvDocenteAsignatura::updateOrCreate($where, [
                    'horas'  => $v['horas'] ?? null,
                    'activo' => $v['activo'] ?? true,
                ]);

                $this->auditoria->auditar(
                    'nv.docente_asignatura','id','', (string)$row->id,'INSERT',
                    "UPSERT DOC-ASI periodo={$row->id_periodo} paralelo=".($row->id_paralelo??'NULL'),
                    $request
                );
            }
            DB::commit();
            return response()->json(['message'=>'Asignaciones procesadas']);
        }catch(\Throwable $e){
            DB::rollBack();
            return response()->json(['error'=>'Fallo en carga masiva'],500);
        }
    }

    // Actualizar horas/activo/paralelo
    public function update($id, Request $r)
    {
        $v = Validator::make($r->all(),[
            'id_paralelo' => 'nullable|integer|exists:nv.paralelos,id',
            'horas'       => 'nullable|integer|min:0',
            'activo'      => 'boolean'
        ])->validate();

        $row = NvDocenteAsignatura::findOrFail($id);
        $old = $row->toJson();

        if (array_key_exists('id_paralelo',$v)) $row->id_paralelo = $v['id_paralelo'];
        if (array_key_exists('horas',$v))       $row->horas       = $v['horas'];
        if (array_key_exists('activo',$v))      $row->activo      = $v['activo'];
        $row->save();

        $this->auditoria->auditar(
            'nv.docente_asignatura','id',$old,$row->toJson(),'UPDATE',
            "UPDATE asignación {$id}", $r
        );

        return response()->json($row);
    }

    // Eliminar
    public function destroy($id, Request $r)
    {
        $row = NvDocenteAsignatura::findOrFail($id);
        $old = $row->toJson();
        $row->delete();

        $this->auditoria->auditar(
            'nv.docente_asignatura','id',$old,'','DELETE',
            "DELETE asignación {$id}", $r
        );

        return response()->json(['ok'=>true]);
    }
}
