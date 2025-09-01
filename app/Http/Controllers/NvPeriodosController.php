<?php

namespace App\Http\Controllers;

use App\Models\NvPeriodo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class NvPeriodosController extends Controller
{
    private $auditoria;

    public function __construct()
    {
        $this->middleware('auth:api');
        $this->auditoria = new AuditoriaControllers();
    }

    public function index()
    {
        return response()->json(NvPeriodo::orderByDesc('id')->get());
    }

    public function cpuPeriodos()
    {
        $rows = DB::table('public.cpu_periodo as p')
            ->leftJoin('public.cpu_estados as e','e.id','=','p.id_estado')
            ->select('p.id','p.nombre','p.created_at','p.updated_at','p.id_estado','e.estado as estado_nombre')
            ->orderBy('p.id','desc')
            ->get();

        return response()->json($rows);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'id_cpu_periodo'   => 'required|integer|exists:public.cpu_periodo,id',
            'nombre'           => 'required|string|max:255',
            'fecha_inicio'     => 'required|date',
            'fecha_fin'        => 'required|date|after_or_equal:fecha_inicio',
            'asistencia_inicio'=> 'nullable|date',
            'asistencia_fin'   => 'nullable|date|after_or_equal:asistencia_inicio',
            'actas_inicio'     => 'nullable|date',
            'actas_fin'        => 'nullable|date|after_or_equal:actas_inicio',
            'notas_inicio'     => 'nullable|date',
            'notas_fin'        => 'nullable|date|after_or_equal:notas_inicio',
            'activo'           => 'boolean'
        ]);

        if ($validator->fails()) return response()->json(['error'=>$validator->errors()], 422);

        DB::beginTransaction();
        try {
            $periodo = NvPeriodo::create($validator->validated() + [
                'id_usuario' => $request->user()->id ?? null,
            ]);

            $this->auditoria->auditar('nv.periodos','id','',''.$periodo->id,'INSERT',
                "CONFIGURACIÓN NV PERIODO: {$periodo->nombre} (cpu: {$periodo->id_cpu_periodo})",$request);

            DB::commit();
            return response()->json($periodo, 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error al crear NV periodo: '.$e->getMessage());
            return response()->json(['error'=>'No se pudo crear el periodo'], 500);
        }
    }

    public function update($id, Request $request)
    {
        $validator = Validator::make($request->all(),[
            'id_cpu_periodo'   => 'sometimes|integer|exists:public.cpu_periodo,id',
            'nombre'           => 'sometimes|string|max:255',
            'fecha_inicio'     => 'sometimes|date',
            'fecha_fin'        => 'sometimes|date|after_or_equal:fecha_inicio',
            'asistencia_inicio'=> 'nullable|date',
            'asistencia_fin'   => 'nullable|date|after_or_equal:asistencia_inicio',
            'actas_inicio'     => 'nullable|date',
            'actas_fin'        => 'nullable|date|after_or_equal:actas_inicio',
            'notas_inicio'     => 'nullable|date',
            'notas_fin'        => 'nullable|date|after_or_equal:notas_inicio',
            'activo'           => 'boolean'
        ]);

        if ($validator->fails()) return response()->json(['error'=>$validator->errors()], 422);

        DB::beginTransaction();
        try {
            $periodo = NvPeriodo::findOrFail($id);
            $old = $periodo->toJson();

            $periodo->fill($validator->validated())->save();

            $this->auditoria->auditar('nv.periodos','id',$old,$periodo->toJson(),'UPDATE',
                "ACTUALIZACIÓN NV PERIODO: {$periodo->id}",$request);

            DB::commit();
            return response()->json($periodo);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['error'=>'No se pudo actualizar'], 500);
        }
    }


    // Activar / desactivar (soft)
    public function toggleActivo($id)
    {
        $p = NvPeriodo::findOrFail($id);
        $old = $p->toJson();
        $p->activo = !$p->activo;
        $p->save();

        $this->auditoria->auditar('nv.periodos','activo',$old,$p->toJson(), $p->activo ? 'MODIFICACION':'DESACTIVACION',
            "CAMBIO DE ESTADO NV PERIODO: {$p->id} -> ".($p->activo?'ACTIVO':'INACTIVO'));

        return response()->json($p);
    }

    // Carga masiva / upsert por (id_cpu_periodo)
    public function bulkUpsert(Request $request)
    {
        $rows = $request->input('periodos',[]);
        if (!is_array($rows) || empty($rows)) {
            return response()->json(['error'=>'Formato inválido o vacío'],422);
        }

        DB::beginTransaction();
        try {
            foreach ($rows as $row) {
                $v = Validator::make($row,[
                    'id_cpu_periodo'   => 'required|integer|exists:public.cpu_periodo,id',
                    'nombre'           => 'required|string|max:255',
                    'fecha_inicio'     => 'required|date',
                    'fecha_fin'        => 'required|date|after_or_equal:fecha_inicio',
                    'asistencia_inicio'=> 'nullable|date',
                    'asistencia_fin'   => 'nullable|date|after_or_equal:asistencia_inicio',
                    'actas_inicio'     => 'nullable|date',
                    'actas_fin'        => 'nullable|date|after_or_equal:actas_inicio',
                    'notas_inicio'     => 'nullable|date',
                    'notas_fin'        => 'nullable|date|after_or_equal:notas_inicio',
                    'activo'           => 'boolean'
                ])->validate();

                $periodo = NvPeriodo::updateOrCreate(
                    ['id_cpu_periodo'=>$v['id_cpu_periodo']],
                    array_merge($v, ['id_usuario'=>$request->user()->id ?? null])
                );

                $this->auditoria->auditar('nv.periodos','id','',$periodo->id,'INSERCION',
                    "UPSERT NV PERIODO (cpu: {$periodo->id_cpu_periodo})");
            }
            DB::commit();
            return response()->json(['message'=>'Procesado correctamente']);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('bulkUpsert NV periodos: '.$e->getMessage());
            return response()->json(['error'=>'Fallo en la carga masiva'],500);
        }
    }

}
