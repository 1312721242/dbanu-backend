<?php

namespace App\Http\Controllers;

use App\Models\NvParalelo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class NvParalelosController extends Controller
{
    private $auditoria;

    public function __construct()
    {
        $this->middleware('auth:api');
        $this->auditoria = new AuditoriaControllers();
    }

    public function index(Request $request)
    {
        $q = NvParalelo::query()->orderBy('id');

        if ($request->has('activo')) {
            $q->where('activo', $request->boolean('activo'));
        }

        if ($request->filled('busqueda')) {
            $term = mb_strtolower($request->input('busqueda'));
            $q->whereRaw('LOWER(codigo) LIKE ?', ['%' . $term . '%']);
        }

        return response()->json($q->get());
    }

    public function store(Request $request)
    {
        $v = Validator::make($request->all(), [
            'codigo' => [
                'required',
                'string',
                'max:5',
                'regex:/^[A-Z][0-9]{2,3}$/',
                // ✅ usar el modelo para evitar el problema de "connection [nv]"
                Rule::unique(NvParalelo::class, 'codigo'),
            ],
            'activo' => 'boolean',
        ])->validate();

        $row = NvParalelo::create($v);

        $this->auditoria->auditar(
            'nv.paralelos',
            'id',
            '',
            (string)$row->id,
            'INSERT',
            "CREADO PARALELO {$row->codigo}",
            $request
        );

        return response()->json($row, 201);
    }

    public function toggle($id, Request $request)
    {
        $p = NvParalelo::findOrFail($id);
        $old = $p->toJson();
        $p->activo = !$p->activo;
        $p->save();

        $this->auditoria->auditar(
            'nv.paralelos',
            'activo',
            $old,
            $p->toJson(),
            $p->activo ? 'UPDATE' : 'DISABLED',
            "TOGGLE PARALELO {$p->codigo}",
            $request
        );

        return response()->json($p);
    }

    public function update($id, Request $request)
    {
        $rules = [
            'codigo' => [
                'sometimes',
                'string',
                'max:5',
                'regex:/^[A-Z][0-9]{2,3}$/',
                // ✅ igual que arriba, basado en el modelo y con ignore($id)
                Rule::unique(NvParalelo::class, 'codigo')->ignore($id, 'id'),
            ],
            'activo' => 'boolean',
        ];

        $data = Validator::make($request->all(), $rules)->validate();

        $row = NvParalelo::findOrFail($id);
        $old = $row->toJson();

        if (array_key_exists('codigo', $data)) $row->codigo = $data['codigo'];
        if (array_key_exists('activo', $data)) $row->activo = (bool)$data['activo'];
        $row->save();

        $this->auditoria->auditar(
            'nv.paralelos',
            'id',
            $old,
            $row->toJson(),
            'UPDATE',
            "MODIFICADO PARALELO {$row->codigo} (ID {$row->id})",
            $request
        );

        return response()->json($row);
    }

    public function bulkUpsert(Request $request)
    {
        $items = $request->input('paralelos', []);
        if (!is_array($items) || empty($items)) {
            return response()->json(['error' => 'Vacío o formato inválido'], 422);
        }

        DB::beginTransaction();
        try {
            foreach ($items as $it) {
                $v = Validator::make($it, [
                    'codigo' => [
                        'required','string','max:5','regex:/^[A-Z][0-9]{2,3}$/',
                        // No hace falta unique aquí porque usamos updateOrCreate
                    ],
                    'activo' => 'boolean',
                ])->validate();

                $row = NvParalelo::updateOrCreate(['codigo' => $v['codigo']], $v);

                $this->auditoria->auditar(
                    'nv.paralelos',
                    'id',
                    '',
                    (string)$row->id,
                    'INSERCION',
                    "UPSERT PARALELO {$row->codigo}"
                );
            }
            DB::commit();
            return response()->json(['message' => 'Paralelos procesados']);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['error' => 'Fallo en carga masiva'], 500);
        }
    }
}
