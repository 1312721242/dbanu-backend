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
            ->orderBy('id', 'asc')
            ->get();

        return response()->json($textos);
    }


    public function editarTextosFuncionTres(Request $request)
    {
        // Asumiendo que recibimos un array de datos en el cuerpo de la solicitud
        $datos = $request->all();

        // Verificar si los datos son un solo array o un array de arrays
        if (!is_array(reset($datos))) {
            // Si son un solo array, convertirlo en un array de arrays
            $datos = [$datos];
        }

        // Iterar sobre cada conjunto de datos
        foreach ($datos as $dato) {
            $id = $dato['id'];
            $nuevoTexto = $dato['texto'];

            // Actualizar el texto en la base de datos para el ID proporcionado
            CpuTextosMensajes::where('id', $id)
                ->update(['texto' => $nuevoTexto]);
        }

        return response()->json(['mensaje' => 'Textos actualizados con éxito.']);
    }
}
