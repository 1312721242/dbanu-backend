<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CpuPedidoCostura;
use Illuminate\Support\Facades\DB;

class CpuPedidoCosturaController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'id_persona' => 'required|integer|exists:cpu_personas,id',
            'tipo_usuario' => 'required|string',
            'materiales' => 'nullable|string',
            'prendas' => 'required|array|min:1',
            'prendas.*.id' => 'required|integer',
            'prendas.*.tipo_prenda' => 'required|string',
            'prendas.*.talla' => 'required|string',
            'prendas.*.cantidad' => 'required|integer|min:1',
            'prendas.*.medidas_especificas' => 'nullable|string',
        ]);

        $pedido = CpuPedidoCostura::create([
            'id_persona' => $request->id_persona,
            'tipo_usuario' => $request->tipo_usuario,
            'materiales' => $request->materiales,
            'prendas' => $request->prendas,
        ]);

        return response()->json([
            'message' => 'Pedido registrado correctamente.',
            'pedido' => $pedido,
        ], 201);
    }

    public function index(Request $request)
    {
        // Base query
        $query = DB::table('cpu_pedidos_costura as pc')
            ->join('cpu_personas as p', 'p.id', '=', 'pc.id_persona')
            ->select(
                'pc.id',
                'pc.tipo_usuario',
                'pc.materiales',
                'pc.prendas',
                'pc.created_at',
                'p.id as persona_id',
                'p.cedula',
                'p.nombres',
                'p.ciudad',
                'p.sexo',
                'p.tipoetnia',
                'p.discapacidad',
                'p.estado_civil',
                'p.ocupacion'
            );

        // Filtro por rango de fechas
        if ($request->filled('desde') && $request->filled('hasta')) {
            $query->whereBetween('pc.created_at', [
                $request->desde . ' 00:00:00',
                $request->hasta . ' 23:59:59'
            ]);
        }

        // Filtro por búsqueda (nombre o cédula)
        if ($request->filled('busqueda')) {
            $busqueda = strtolower($request->busqueda);
            $query->where(function ($q) use ($busqueda) {
                $q->whereRaw('LOWER(p.nombres) LIKE ?', ["%{$busqueda}%"])
                    ->orWhere('p.cedula', 'like', "%{$busqueda}%");
            });
        }

        // Ejecutar la consulta
        $pedidos = $query->orderBy('pc.created_at', 'desc')->get();

        // Decodificar JSONB de prendas y normalizar campos
        foreach ($pedidos as $pedido) {
            $pedido->prendas = json_decode($pedido->prendas, true);

            // Normalización de campos importantes
            $pedido->ciudad = $pedido->ciudad ? strtoupper(trim($pedido->ciudad)) : null;
            $pedido->sexo = $pedido->sexo ? strtoupper(trim($pedido->sexo)) : null;
            $pedido->tipoetnia = $pedido->tipoetnia ? strtoupper(trim($pedido->tipoetnia)) : null;
            $pedido->estado_civil = $pedido->estado_civil ? strtoupper(trim($pedido->estado_civil)) : null;
            $pedido->ocupacion = $pedido->ocupacion ? strtoupper(trim($pedido->ocupacion)) : null;
            $pedido->discapacidad = $pedido->discapacidad ? strtoupper(trim($pedido->discapacidad)) : null;
            $pedido->tipo_usuario = $pedido->tipo_usuario ? strtoupper(trim($pedido->tipo_usuario)) : null;
        }

        // Extraer valores únicos para filtros
        $sexo = $pedidos->pluck('sexo')->filter()->unique()->values();
        $ciudad = $pedidos->pluck('ciudad')->filter()->unique()->values();
        $tipo_usuario = $pedidos->pluck('tipo_usuario')->filter()->unique()->values();
        $discapacidad = $pedidos->pluck('discapacidad')->filter()->unique()->values();
        $etnia = $pedidos->pluck('tipoetnia')->filter()->unique()->values();
        $estado_civil = $pedidos->pluck('estado_civil')->filter()->unique()->values();
        $ocupacion = $pedidos->pluck('ocupacion')->filter()->unique()->values();

        // Tallas desde las prendas
        $tallas = collect();
        foreach ($pedidos as $pedido) {
            foreach ($pedido->prendas as $prenda) {
                if (isset($prenda['talla'])) {
                    $tallas->push($prenda['talla']);
                }
            }
        }
        $tallas = $tallas->unique()->values();

        // Armar respuesta
        return response()->json([
            'pedidos' => $pedidos,
            'filtros' => [
                'sexo' => $sexo,
                'ciudad' => $ciudad,
                'tipo_usuario' => $tipo_usuario,
                'discapacidad' => $discapacidad,
                'tipoetnia' => $etnia,
                'estado_civil' => $estado_civil,
                'ocupacion' => $ocupacion,
                'tallas' => $tallas
            ]
        ]);
    }


    public function update(Request $request, $id)
    {
        $request->validate([
            'id_persona' => 'required|integer|exists:cpu_personas,id',
            'tipo_usuario' => 'required|string',
            'materiales' => 'nullable|string',
            'prendas' => 'required|array|min:1',
            'prendas.*.tipo_prenda' => 'required|string',
            'prendas.*.talla' => 'required|string',
            'prendas.*.cantidad' => 'required|integer|min:1',
            'prendas.*.medidas_especificas' => 'nullable|string',
        ]);

        $pedido = CpuPedidoCostura::findOrFail($id);
        $pedido->id_persona = $request->id_persona;
        $pedido->tipo_usuario = $request->tipo_usuario;
        $pedido->materiales = $request->materiales;
        $pedido->prendas = $request->prendas;
        $pedido->save();

        return response()->json([
            'message' => 'Pedido actualizado correctamente.',
            'pedido' => $pedido,
        ]);
    }
}
