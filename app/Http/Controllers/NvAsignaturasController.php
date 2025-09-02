<?php

namespace App\Http\Controllers;

use App\Models\NvAsignatura;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class NvAsignaturasController extends Controller
{
    private $auditoria;

    public function __construct()
    {
        $this->middleware('auth:api');
        $this->auditoria = new AuditoriaControllers();
    }

    /**
     * GET /nv/asignaturas
     * Filtros opcionales:
     *   - activo (true/false)
     *   - busqueda (match por codigo o nombre, case-insensitive)
     */
    public function index(Request $request)
    {
        $q = NvAsignatura::query()->orderBy('codigo');

        if ($request->has('activo')) {
            $q->where('activo', $request->boolean('activo'));
        }

        if ($request->filled('busqueda')) {
            $term = mb_strtolower($request->input('busqueda'));
            $q->where(function ($qq) use ($term) {
                $qq->whereRaw('LOWER(codigo) LIKE ?', ['%' . $term . '%'])
                   ->orWhereRaw('LOWER(nombre) LIKE ?', ['%' . $term . '%']);
            });
        }

        return response()->json($q->get());
    }

    /**
     * POST /nv/asignaturas
     */
    public function store(Request $request)
    {
        $v = Validator::make($request->all(), [
            'codigo'          => ['required','string','max:50', Rule::unique(NvAsignatura::class, 'codigo')], // ✅ modelo, no string "nv.asignaturas"
            'nombre'          => ['required','string','max:255'],
            'creditos'        => ['nullable','integer','min:0'],
            'horas_semanales' => ['nullable','integer','min:0'],
            'tipo'            => ['nullable','string','max:20'],
            'activo'          => ['boolean'],
        ])->validate();

        $row = NvAsignatura::create($v + ['id_usuario' => $request->user()->id ?? null]);

        $this->auditoria->auditar(
            'nv.asignaturas',
            'id',
            '',
            (string)$row->id,
            'INSERT',
            "NUEVA ASIGNATURA {$row->codigo} - {$row->nombre}",
            $request
        );

        return response()->json($row, 201);
    }

    /**
     * PATCH /nv/asignaturas/{id}/toggle
     */
    public function toggle($id, Request $request)
    {
        $row = NvAsignatura::findOrFail($id);
        $old = $row->toJson();

        $row->activo = !$row->activo;
        $row->save();

        $this->auditoria->auditar(
            'nv.asignaturas',
            'activo',
            $old,
            $row->toJson(),
            $row->activo ? 'UPDATE' : 'DISABLED',
            "TOGGLE ASIGNATURA {$row->codigo}",
            $request
        );

        return response()->json($row);
    }

    /**
     * PUT /nv/asignaturas/{id}
     */
    public function update($id, Request $request)
    {
        $rules = [
            'codigo'          => ['sometimes','string','max:50', Rule::unique(NvAsignatura::class, 'codigo')->ignore($id, 'id')], // ✅ modelo + ignore
            'nombre'          => ['sometimes','string','max:255'],
            'creditos'        => ['sometimes','nullable','integer','min:0'],
            'horas_semanales' => ['sometimes','nullable','integer','min:0'],
            'tipo'            => ['sometimes','nullable','string','max:20'],
            'activo'          => ['sometimes','boolean'],
        ];

        $data = Validator::make($request->all(), $rules)->validate();

        $row = NvAsignatura::findOrFail($id);
        $old = $row->toJson();

        foreach (['codigo','nombre','creditos','horas_semanales','tipo'] as $f) {
            if (array_key_exists($f, $data)) $row->{$f} = $data[$f];
        }
        if (array_key_exists('activo', $data)) $row->activo = (bool)$data['activo'];

        if ($request->user() && !isset($row->id_usuario)) {
            $row->id_usuario = $request->user()->id;
        }

        $row->save();

        $this->auditoria->auditar(
            'nv.asignaturas',
            'id',
            $old,
            $row->toJson(),
            'UPDATE',
            "MODIFICADA ASIGNATURA {$row->codigo} (ID {$row->id})",
            $request
        );

        return response()->json($row);
    }

    /**
     * POST /nv/asignaturas/bulk
     */
    public function bulkUpsert(Request $request)
    {
        $items = $request->input('asignaturas', []);
        if (!is_array($items) || empty($items)) {
            return response()->json(['error' => 'Formato inválido o vacío'], 422);
        }

        DB::beginTransaction();
        try {
            foreach ($items as $it) {
                $v = Validator::make($it, [
                    'codigo'          => ['required','string','max:50'],
                    'nombre'          => ['required','string','max:255'],
                    'creditos'        => ['nullable','integer','min:0'],
                    'horas_semanales' => ['nullable','integer','min:0'],
                    'tipo'            => ['nullable','string','max:20'],
                    'activo'          => ['boolean'],
                ])->validate();

                $row = NvAsignatura::updateOrCreate(
                    ['codigo' => $v['codigo']],
                    $v + ['id_usuario' => $request->user()->id ?? null]
                );

                $this->auditoria->auditar(
                    'nv.asignaturas',
                    'id',
                    '',
                    (string)$row->id,
                    'INSERCION',
                    "UPSERT ASIGNATURA {$row->codigo}"
                );
            }

            DB::commit();
            return response()->json(['message' => 'Asignaturas procesadas correctamente']);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['error' => 'Fallo en carga masiva de asignaturas'], 500);
        }
    }

    public function destroy($id, Request $request)
    {
        $row = NvAsignatura::findOrFail($id);
        $old = $row->toJson();
        $row->delete();

        $this->auditoria->auditar(
            'nv.asignaturas',
            'id',
            $old,
            '',
            'DELETE',
            "ELIMINADA ASIGNATURA {$row->codigo}",
            $request
        );

        return response()->json(['message' => 'Eliminado correctamente']);
    }
}
