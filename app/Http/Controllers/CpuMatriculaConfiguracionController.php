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

}
