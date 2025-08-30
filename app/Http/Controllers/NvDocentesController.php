<?php

namespace App\Http\Controllers;

use App\Models\NvDocente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User; // Asegúrate de tener este modelo
use Illuminate\Database\QueryException;

class NvDocentesController extends Controller
{
    private $auditoria;

    public function __construct()
    {
        $this->middleware('auth:api');
        $this->auditoria = new AuditoriaControllers();
    }

    public function index()
    {
        return response()->json(
            NvDocente::orderBy('apellidos')->orderBy('nombres')->get()
        );
    }

    public function store(Request $request)
    {
        // ⚠️ Usar Rule::unique(Model::class, 'columna'); evita que "nv" sea tomada como conexión
        $data = Validator::make($request->all(), [
            'identificacion' => ['required', 'string', 'max:50', Rule::unique(NvDocente::class, 'identificacion')],
            'nombres'        => ['required', 'string', 'max:255'],
            'apellidos'      => ['required', 'string', 'max:255'],
            'correo'         => ['nullable', 'email', Rule::unique(NvDocente::class, 'correo')],
            'telefono'       => ['nullable', 'string', 'max:50'],
            'titulo'         => ['nullable', 'string', 'max:100'],
            'dedicacion'     => ['nullable', 'string', 'max:20'],
            'activo'         => ['boolean'],
        ])->validate();

        // Debe existir un usuario cuya api_token == identificacion (cédula)
        $user = DB::table(DB::raw('public.users'))->where('api_token', $data['identificacion'])->first();
        if (!$user) {
            return response()->json([
                'error' => 'Primero debe existir el usuario con esa cédula (api_token) en la tabla users.'
            ], 422);
        }

        // Evitar duplicación por id_usuario
        $yaVinculado = DB::table(DB::raw('nv.docentes'))->where('id_usuario', $user->id)->exists();
        if ($yaVinculado) {
            return response()->json(['error' => 'Ese usuario ya está vinculado como docente.'], 422);
        }

        $doc = NvDocente::create($data + ['id_usuario' => $user->id]);

        $this->auditoria->auditar(
            'nv.docentes',
            'id',
            '',
            (string)$doc->id,
            'INSERT',
            "NUEVO DOCENTE {$doc->apellidos} {$doc->nombres} ({$doc->identificacion})",
            $request
        );

        return response()->json($doc, 201);
    }

    // Carga masiva / upsert con creación de usuarios y asignación de funciones (rol=25)
   // ===== CARGA MASIVA ROBUSTA, con contadores y tolerante a duplicados =====
public function bulkUpsert(Request $request)
{
    $rows = $request->input('docentes', []);
    if (!is_array($rows) || empty($rows)) {
        return response()->json(['error' => 'Formato inválido o vacío'], 422);
    }

    // Constantes negocio
    $ROL_DOCENTE_ID   = 25;
    $SEDE_ID          = 1;
    $FACULTAD_ID      = 16;
    $CARRERA_ID       = 64;

    // Funciones del rol (cacheadas)
    $roleFuncs = DB::table('public.cpu_userrolefunction')
        ->select('id_usermenu','id_userrole','nombre','accion','id_menu')
        ->where('id_userrole',$ROL_DOCENTE_ID)
        ->get();

    $stats = [
        'docentes_creados'      => 0,
        'docentes_actualizados' => 0,
        'usuarios_creados'      => 0,
        'usuarios_existentes'   => 0,
        'funciones_asignadas'   => 0,
        'errores'               => [],
        'procesados'            => 0,
    ];

    DB::beginTransaction();
    try {
        foreach ($rows as $idx => $row) {
            $fila = $idx + 1;

            // 1) Validación de cada fila
            try {
                $v = Validator::make($row, [
                    'identificacion' => ['required','string','max:50'],
                    'nombres'        => ['required','string','max:255'],
                    'apellidos'      => ['required','string','max:255'],
                    'correo'         => ['nullable','email'],
                    'telefono'       => ['nullable','string','max:50'],
                    'titulo'         => ['nullable','string','max:100'],
                    'dedicacion'     => ['nullable','string','max:20'],
                    'activo'         => ['boolean'],
                ])->validate();
            } catch (\Throwable $ve) {
                $stats['errores'][] = "Fila {$fila}: ". $ve->getMessage();
                continue;
            }

            // 2) Usuario por EMAIL (si trae)
            $userId = null;
            if (!empty($v['correo'])) {
                try {
                    // ¿ya existe por email?
                    $user = DB::table('public.users')->where('email',$v['correo'])->first();

                    if (!$user) {
                        // si NO existe, verificar que el api_token (cédula) no choque con otro usuario
                        $tokClash = DB::table('public.users')->where('api_token',$v['identificacion'])->exists();
                        if ($tokClash) {
                            // no crear usuario, pero registrar error y continuar con docente sin id_usuario
                            $stats['errores'][] = "Fila {$fila}: ya existe un usuario con esa CÉDULA como api_token.";
                        } else {
                            // crear usuario
                            $nombreCompleto = trim(mb_strtoupper($v['apellidos'].' '.$v['nombres'],'UTF-8'));
                            $userId = DB::table('public.users')->insertGetId([
                                'name'          => $nombreCompleto,
                                'email'         => $v['correo'],
                                'password'      => Hash::make($v['identificacion']),
                                'usr_tipo'      => $ROL_DOCENTE_ID,
                                'usr_sede'      => $SEDE_ID,
                                'usr_facultad'  => $FACULTAD_ID,
                                'usr_carrera'   => $CARRERA_ID,
                                'usr_profesion' => null,
                                'api_token'     => $v['identificacion'],
                                'created_at'    => now(),
                                'updated_at'    => now(),
                            ]);
                            $stats['usuarios_creados']++;

                            // asignar funciones de rol 25 (idempotente)
                            foreach ($roleFuncs as $rf) {
                                $ya = DB::table('public.cpu_userfunction')->where([
                                    ['id_users','=',$userId],
                                    ['id_usermenu','=',$rf->id_usermenu],
                                    ['id_userrole','=',$ROL_DOCENTE_ID],
                                    ['accion','=',$rf->accion],
                                ])->exists();
                                if (!$ya) {
                                    DB::table('public.cpu_userfunction')->insert([
                                        'id_users'    => $userId,
                                        'id_usermenu' => $rf->id_usermenu,
                                        'id_userrole' => $ROL_DOCENTE_ID,
                                        'nombre'      => $rf->nombre,
                                        'accion'      => $rf->accion,
                                        'id_menu'     => $rf->id_menu,
                                        'created_at'  => now(),
                                        'updated_at'  => now(),
                                    ]);
                                    $stats['funciones_asignadas']++;
                                }
                            }
                        }
                    } else {
                        // existe por email
                        $userId = $user->id;
                        $stats['usuarios_existentes']++;

                        // asegurar api_token si está vacío (sin romper unique)
                        if (empty($user->api_token)) {
                            // sólo setear si NADIE más tiene esa cédula como api_token
                            $tokClash = DB::table('public.users')->where('api_token',$v['identificacion'])->where('id','<>',$userId)->exists();
                            if (!$tokClash) {
                                DB::table('public.users')->where('id',$userId)
                                    ->update(['api_token'=>$v['identificacion'],'updated_at'=>now()]);
                            } else {
                                $stats['errores'][] = "Fila {$fila}: el api_token {$v['identificacion']} ya está usado por otro usuario.";
                            }
                        }

                        // funciones del rol, idempotente
                        foreach ($roleFuncs as $rf) {
                            $ya = DB::table('public.cpu_userfunction')->where([
                                ['id_users','=',$userId],
                                ['id_usermenu','=',$rf->id_usermenu],
                                ['id_userrole','=',$ROL_DOCENTE_ID],
                                ['accion','=',$rf->accion],
                            ])->exists();
                            if (!$ya) {
                                DB::table('public.cpu_userfunction')->insert([
                                    'id_users'    => $userId,
                                    'id_usermenu' => $rf->id_usermenu,
                                    'id_userrole' => $ROL_DOCENTE_ID,
                                    'nombre'      => $rf->nombre,
                                    'accion'      => $rf->accion,
                                    'id_menu'     => $rf->id_menu,
                                    'created_at'  => now(),
                                    'updated_at'  => now(),
                                ]);
                                $stats['funciones_asignadas']++;
                            }
                        }
                    }
                } catch (QueryException $qe) {
                    // atrapar violaciones de unique (email/api_token)
                    $stats['errores'][] = "Fila {$fila}: no se pudo crear/actualizar usuario (".$qe->getCode().")";
                }
            } else {
                $stats['errores'][] = "Fila {$fila}: sin CORREO; no se crea/relaciona usuario.";
            }

            // 3) Upsert docente por identificacion (sin romper por duplicados)
            try {
                $payloadDoc = [
                    'identificacion' => $v['identificacion'],
                    'nombres'        => mb_strtoupper($v['nombres'],'UTF-8'),
                    'apellidos'      => mb_strtoupper($v['apellidos'],'UTF-8'),
                    'correo'         => $v['correo'] ?? null,
                    'telefono'       => $v['telefono'] ?? null,
                    'titulo'         => isset($v['titulo']) ? mb_strtoupper($v['titulo'],'UTF-8') : null,
                    'dedicacion'     => $v['dedicacion'] ?? null,
                    'activo'         => array_key_exists('activo',$v)? (bool)$v['activo'] : true,
                ];
                if ($userId) $payloadDoc['id_usuario'] = $userId;

                $exists = DB::table('nv.docentes')->where('identificacion',$v['identificacion'])->first();

                if ($exists) {
                    DB::table('nv.docentes')->where('id',$exists->id)
                        ->update($payloadDoc + ['updated_at'=>now()]);
                    $stats['docentes_actualizados']++;

                    $this->auditoria('nv.docentes','id',json_encode($exists),json_encode($payloadDoc),'UPDATE',"UPSERT DOCENTE (UPDATE) {$v['identificacion']}");
                } else {
                    $docId = DB::table('nv.docentes')->insertGetId($payloadDoc + ['created_at'=>now(),'updated_at'=>now()]);
                    $stats['docentes_creados']++;

                    $this->auditoria('nv.docentes','id','',(string)$docId,'INSERT',"UPSERT DOCENTE (INSERT) {$v['identificacion']}");
                }

                $stats['procesados']++;
            } catch (QueryException $qe) {
                // p.ej. unique en correo dentro de nv.docentes
                $stats['errores'][] = "Fila {$fila}: no se pudo upsert DOCENTE (".$qe->getCode().")";
            }
        }

        DB::commit();
        return response()->json([
            'message' => 'Docentes procesados',
            'stats'   => $stats
        ]);
    } catch (\Throwable $e) {
        DB::rollBack();
        return response()->json([
            'error'   => 'Fallo en carga masiva',
            'detalle' => $e->getMessage()
        ], 500);
    }
}


    /**
     * Sugerencias de usuarios (por cédula en api_token o por nombre).
     * Acepta ?q= o ?bus= para ser compatible con axios_client.buscarUsuarios (usa "bus").
     * GET /users/search
     */
    public function buscarUsuarios(Request $request)
    {
        $q = trim((string)($request->query('q', $request->query('bus', ''))));
        if ($q === '') {
            return response()->json([]);
        }

        $isCedula = preg_match('/^\d+$/', $q) === 1;

        $usuarios = DB::table(DB::raw('public.users as u'))
            ->select('u.id', 'u.name', 'u.email', 'u.api_token', 'u.foto_perfil', 'u.usr_tipo', 'u.usr_estado')
            ->when($isCedula, function ($s) use ($q) {
                return $s->where('u.api_token', 'ilike', $q . '%');
            }, function ($s) use ($q) {
                return $s->where('u.name', 'ilike', '%' . $q . '%');
            })
            ->orderBy('u.name')
            ->limit(20)
            ->get();

        if ($usuarios->isEmpty()) {
            return response()->json([]);
        }

        $cedulas = $usuarios->pluck('api_token')->filter()->values();
        $ids     = $usuarios->pluck('id')->values();

        $docPorCedula = DB::table(DB::raw('nv.docentes'))
            ->select('identificacion', 'id as docente_id')
            ->whereIn('identificacion', $cedulas)
            ->get()
            ->keyBy('identificacion');

        $docPorUserId = DB::table(DB::raw('nv.docentes'))
            ->select('id_usuario', 'id as docente_id')
            ->whereIn('id_usuario', $ids)
            ->get()
            ->keyBy('id_usuario');

        $resultado = $usuarios->map(function ($u) use ($docPorCedula, $docPorUserId) {
            $yaDoc = false;
            $docId = null;

            if ($u->api_token && isset($docPorCedula[$u->api_token])) {
                $yaDoc = true;
                $docId = $docPorCedula[$u->api_token]->docente_id;
            } elseif (isset($docPorUserId[$u->id])) {
                $yaDoc = true;
                $docId = $docPorUserId[$u->id]->docente_id;
            }

            return [
                'id'            => $u->id,
                'name'          => $u->name,
                'email'         => $u->email,
                'api_token'     => $u->api_token,
                'foto_perfil'   => $u->foto_perfil,
                'usr_tipo'      => $u->usr_tipo,
                'usr_estado'    => $u->usr_estado,
                'ya_es_docente' => $yaDoc,
                'docente_id'    => $docId,
            ];
        });

        return response()->json($resultado);
    }

    /**
     * Buscar docentes (por cédula o nombres/apellidos)
     * GET /nv/docentes/buscar?q=
     */
    public function buscarDocentes(Request $request)
    {
        $q = trim((string)$request->query('q', ''));
        if ($q === '') {
            return response()->json([]);
        }

        $isCedula = preg_match('/^\d+$/', $q) === 1;

        $docentes = DB::table(DB::raw('nv.docentes as d'))
            ->leftJoin(DB::raw('public.users as u'), 'u.id', '=', 'd.id_usuario')
            ->select(
                'd.id',
                'd.identificacion',
                'd.nombres',
                'd.apellidos',
                'd.correo',
                'd.telefono',
                'd.titulo',
                'd.dedicacion',
                'd.activo',
                'd.id_usuario',
                'u.name as user_name',
                'u.email as user_email',
                'u.foto_perfil'
            )
            ->when($isCedula, function ($s) use ($q) {
                return $s->where('d.identificacion', 'ilike', $q . '%');
            }, function ($s) use ($q) {
                return $s->where(function ($w) use ($q) {
                    $w->where('d.nombres', 'ilike', '%' . $q . '%')
                        ->orWhere('d.apellidos', 'ilike', '%' . $q . '%');
                });
            })
            ->orderBy('d.apellidos')
            ->orderBy('d.nombres')
            ->limit(20)
            ->get();

        return response()->json($docentes);
    }

    /**
     * Crear docente a partir de un user existente (id)
     * POST /nv/docentes/crear-desde-usuario
     */
    public function crearDesdeUsuario(Request $request)
    {
        $data = Validator::make($request->all(), [
            'user_id'     => 'required|integer|exists:public.users,id',
            'nombres'     => 'nullable|string|max:255',
            'apellidos'   => 'nullable|string|max:255',
            'correo'      => 'nullable|email',
            'telefono'    => 'nullable|string|max:50',
            'titulo'      => 'nullable|string|max:100',
            'dedicacion'  => 'nullable|string|max:20',
            'activo'      => 'nullable|boolean',
        ])->validate();

        $user = DB::table(DB::raw('public.users'))->where('id', $data['user_id'])->first();
        if (!$user) {
            return response()->json(['error' => 'Usuario no encontrado'], 404);
        }

        if (!$user->api_token) {
            return response()->json(['error' => 'El usuario no tiene cédula (api_token) asignada'], 422);
        }

        $existe = DB::table(DB::raw('nv.docentes'))
            ->where('identificacion', $user->api_token)
            ->orWhere('id_usuario', $user->id)
            ->exists();

        if ($existe) {
            return response()->json(['error' => 'Este usuario/cédula ya está registrado como docente'], 422);
        }

        // Derivar nombres/apellidos de name si no llegan
        $nombres   = $data['nombres']   ?? null;
        $apellidos = $data['apellidos'] ?? null;

        if (!$nombres || !$apellidos) {
            $partes = preg_split('/\s+/', trim($user->name ?? ''), -1, PREG_SPLIT_NO_EMPTY);
            if (count($partes) >= 2) {
                $nombres   = $nombres   ?: implode(' ', array_slice($partes, 0, 2));
                $apellidos = $apellidos ?: implode(' ', array_slice($partes, 2)) ?: ($partes[1] ?? '');
            } else {
                $nombres   = $nombres   ?: ($partes[0] ?? 'N/D');
                $apellidos = $apellidos ?: 'N/D';
            }
        }

        $payload = [
            'identificacion' => $user->api_token,
            'nombres'        => $nombres,
            'apellidos'      => $apellidos,
            'correo'         => $data['correo']     ?? $user->email,
            'telefono'       => $data['telefono']   ?? null,
            'titulo'         => $data['titulo']     ?? null,
            'dedicacion'     => $data['dedicacion'] ?? null,
            'activo'         => $data['activo']     ?? true,
            'id_usuario'     => $user->id,
        ];

        // Validación final (sin “nv.” en unique)
        Validator::make($payload, [
            'identificacion' => ['required', 'string', 'max:50', Rule::unique(NvDocente::class, 'identificacion')],
            'nombres'        => ['required', 'string', 'max:255'],
            'apellidos'      => ['required', 'string', 'max:255'],
            'correo'         => ['nullable', 'email', Rule::unique(NvDocente::class, 'correo')],
            'telefono'       => ['nullable', 'string', 'max:50'],
            'titulo'         => ['nullable', 'string', 'max:100'],
            'dedicacion'     => ['nullable', 'string', 'max:20'],
            'activo'         => ['boolean'],
            'id_usuario'     => ['nullable', 'integer'],
        ])->validate();

        $doc = NvDocente::create($payload);

        $this->auditoria->auditar(
            'nv.docentes',
            'id',
            '',
            (string)$doc->id,
            'INSERT',
            "DOCENTE DESDE USUARIO {$doc->apellidos} {$doc->nombres} ({$doc->identificacion})",
            $request
        );

        return response()->json($doc, 201);
    }

    public function update($id, Request $request)
    {
        $doc = NvDocente::findOrFail($id);
        $old = $doc->toJson();

        // Validación con "Rule::unique" referenciando el Modelo (evita el problema de "connection [nv]")
        $data = Validator::make($request->all(), [
            'identificacion' => ['sometimes', 'string', 'max:50', Rule::unique(NvDocente::class, 'identificacion')->ignore($doc->id, 'id')],
            'nombres'        => ['sometimes', 'string', 'max:255'],
            'apellidos'      => ['sometimes', 'string', 'max:255'],
            'correo'         => ['sometimes', 'nullable', 'email', Rule::unique(NvDocente::class, 'correo')->ignore($doc->id, 'id')],
            'telefono'       => ['sometimes', 'nullable', 'string', 'max:50'],
            'titulo'         => ['sometimes', 'nullable', 'string', 'max:100'],
            'dedicacion'     => ['sometimes', 'nullable', 'string', 'max:20'],
            'activo'         => ['sometimes', 'boolean'],
        ])->validate();

        // Asignación segura
        foreach (['identificacion', 'nombres', 'apellidos', 'correo', 'telefono', 'titulo', 'dedicacion'] as $f) {
            if (array_key_exists($f, $data)) {
                $doc->{$f} = $data[$f];
            }
        }
        if (array_key_exists('activo', $data)) {
            $doc->activo = (bool)$data['activo'];
        }

        // Si quieres permitir cambiar la vinculación del usuario (opcional)
        if (array_key_exists('id_usuario', $request->all())) {
            $doc->id_usuario = $request->input('id_usuario');
        }

        $doc->save();

        $this->auditoria->auditar(
            'nv.docentes',
            'id',
            $old,
            $doc->toJson(),
            'UPDATE',
            "MODIFICADO DOCENTE {$doc->identificacion}",
            $request
        );

        return response()->json($doc);
    }

    public function toggle($id, Request $request)
    {
        $doc = NvDocente::findOrFail($id);
        $old = $doc->toJson();

        $doc->activo = !$doc->activo;
        $doc->save();

        $this->auditoria->auditar(
            'nv.docentes',
            'activo',
            $old,
            $doc->toJson(),
            $doc->activo ? 'UPDATE' : 'DISABLED',
            "TOGGLE DOCENTE {$doc->identificacion}",
            $request
        );

        return response()->json($doc);
    }

    /** proxy auditoría (igual que en NvPeriodosController) **/
    private function auditar($tabla, $campo, $dataOld, $dataNew, $tipo, $descripcion, $request = null)
    {
        return app(NvPeriodosController::class)->auditar($tabla, $campo, $dataOld, $dataNew, $tipo, $descripcion, $request);
    }
}
