<?php

namespace App\Http\Controllers;
use App\Models\CpuTextosMensajes;
use App\Models\CpuFuncionesTextos;


use Illuminate\Http\Request;

class CpuTextosMensajesController extends Controller
{

    public function __construct()
        {
            $this->middleware('auth:sanctum');
        } 
    public function obtenerTextosFuncionTres()
        {
            $textos = CpuTextosMensajes::where('id_funciones_texto', 3)
                ->join('cpu_funciones_textos', 'cpu_textos_mensajes.id_funciones_texto', '=', 'cpu_funciones_textos.id')
                ->select('cpu_textos_mensajes.*', 'cpu_funciones_textos.descripcion')
                ->get();

            return response()->json($textos);
        }
        
}
