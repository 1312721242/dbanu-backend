<?php

namespace App\Http\Controllers;

use App\Models\CpuMatriculaConfiguracion;
use Illuminate\Http\Request;

class CpuMatriculaConfiguracionController extends Controller
{
    // public function __construct()
    // {
    //     $this->middleware('auth:api');
    // }
    public function index()
    {
        return CpuMatriculaConfiguracion::all();
    }

    public function show($id)
    {
        return CpuMatriculaConfiguracion::findOrFail($id);
    }

    public function fechasMatricula($id_periodo)
    {
        // Devuelve todos los registros donde 'id_periodo' es igual al parámetro recibido
        return CpuMatriculaConfiguracion::where('id_periodo', $id_periodo)->get();
    }

    public function periodoActivo()
    {
        return CpuMatriculaConfiguracion::where('id_estado', 8)->get();
    }
}
