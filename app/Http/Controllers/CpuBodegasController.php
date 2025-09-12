<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class CpuBodegasController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
        $this->auditoriaController = new AuditoriaControllers();
        $this->logController = new LogController();
    }

    public function getBodegas($idSede, $idFacultad)
    {
        $data = DB::select('SELECT * 
            FROM cpu_bodegas 
            WHERE bod_id_sede = :idSede 
              AND bod_id_facultad = :idFacultad
              AND bod_estado = 8
            ORDER BY bod_nombre ASC', [
            'idSede' => $idSede,
            'idFacultad' => $idFacultad
        ]);
        return response()->json($data);
    }


 public function getIdBodegas($id_sede, $id_facultad, $id_bodega)
{
    $query = DB::table('cpu_stock_bodegas as sb')
        ->join('cpu_bodegas as b', 'sb.sb_id_bodega', '=', 'b.bod_id')
        ->leftJoin('cpu_sede as s', 's.id', '=', 'b.bod_id_sede')
        ->leftJoin('cpu_facultad as f', 'f.id', '=', 'b.bod_id_facultad')
        ->join('cpu_insumo as i', 'i.id', '=', 'sb.sb_id_insumo')
        ->join('cpu_estados as e', 'e.id', '=', 'i.id_estado')
        ->select(
            'sb.sb_id',
            'sb.sb_cantidad as stock_bodega',
            'sb.sb_stock_minimo',
            'sb.sb_id_bodega',
            'b.bod_nombre as nombre_bodega',
            'b.bod_id_sede',
            's.nombre_sede',
            'b.bod_id_facultad',
            'f.fac_nombre',
            'i.id as id_insumo',
            'i.codigo',
            'i.ins_descripcion',
            'i.id_tipo_insumo',
            'i.estado_insumo',
            'i.id_estado',
            'e.estado',
            'i.modo_adquirido'
        )
        ->where('i.id_estado', 8);

    if ($id_sede) {
        $query->where('b.bod_id_sede', $id_sede);
    }
    if ($id_facultad) {
        $query->where('b.bod_id_facultad', $id_facultad);
    }
    if ($id_bodega) {
        $query->where('sb.sb_id_bodega', $id_bodega);
    }

    $data = $query->orderByDesc('i.id')->get();

    return response()->json($data);
}


    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
