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

        return $data;
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
