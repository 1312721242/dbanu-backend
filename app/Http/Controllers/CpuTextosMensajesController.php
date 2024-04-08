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


    public function editarTextosFuncionTres(Request $request)
    {
        // Validación de la entrada
        $request->validate([
            'id' => 'required|integer', // Asegúrate de que el ID esté presente y sea un entero
            'texto' => 'required|string',
        ]);

        // Obtener el ID de la solicitud
        $id = $request->input('id');

        // Verificar si el ID existe en la base de datos
        $texto = CpuTextosMensajes::find($id);

        if (!$texto) {
            return response()->json(['mensaje' => 'El ID proporcionado no existe en la base de datos.'], 404);
        }

        // Asumiendo que recibimos el nuevo texto como parte del request
        $nuevoTexto = $request->input('texto');

        // Actualizando texto del mensaje
        CpuTextosMensajes::where('id', $id)->update(['texto' => $nuevoTexto]);

        // Devolver una respuesta que confirme la actualización
        return response()->json(['mensaje' => 'Texto actualizado con éxito.']);
    }
}
