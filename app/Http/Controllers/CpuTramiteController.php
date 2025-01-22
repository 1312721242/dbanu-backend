<?php

namespace App\Http\Controllers;

use App\Models\CpuTramite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class CpuTramiteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $tramite = CpuTramite::all();
        return response()->json($tramite);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        // Validaciones
        $validatedData = $request->validate([
            'tra_person_recibe' => 'nullable|integer',
            'tra_tipo' => 'nullable|string',
            'tra_fecha_recibido' => 'nullable|date',
            'tra_fecha_documento' => 'nullable|date',
            'tra_num_documento' => 'nullable|string',
            'tra_suscrito' => 'nullable|string',
            'tra_direccion' => 'nullable|string',
            'tra_asunto' => 'nullable|string',
            'tra_area_derivada' => 'nullable|integer',
            'tra_fecha_derivacion' => 'nullable|date',
            'tra_fecha_contestacion' => 'nullable|date',
            'tra_num_contestacion' => 'nullable|string',
            'tra_direccion_enviada' => 'nullable|string',
            'tra_observacion' => 'nullable|string',
            'tra_estado_tramite' => 'nullable|integer',
            'tra_link_receptado' => 'nullable|string',
            'tra_link_enviado' => 'nullable|string',
            'tra_cargo' => 'nullable|string',
            'created_at' => 'nullable|date',
            'updated_at' => 'nullable|date',
        ]);

        // Iniciar transacción
        DB::beginTransaction();

        try {
            // Crear nuevo trámite
            $tramite = new CpuTramite($validatedData);
            $tramite->save();

            // Confirmar transacción
            DB::commit();

            return response()->json([
                'message' => 'Trámite creado exitosamente',
                'data' => $tramite
            ], 201);

        } catch (\Exception $e) {
            // Revertir transacción en caso de error
            DB::rollBack();

            return response()->json([
                'message' => 'Error al crear el trámite',
                'error' => $e->getMessage()
            ], 500);
        }
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
    public function show(Request $request)
    {
        $fechaInicio = $request->query('fechaInicio');
        $fechaFin = $request->query('fechaFin');

        if (!$fechaInicio || !$fechaFin) {
            return response()->json([
                'message' => 'Las fechas de inicio y fin son requeridas'
            ], 400);
        }

        // Obtener trámites dentro del rango de fechas proporcionado
        $tramitesEnRango = CpuTramite::whereBetween('tra_fecha_recibido', [$fechaInicio, $fechaFin])->get();

        // Obtener trámites a partir del 01-01-2024 hasta la fecha actual con estado diferente de "FINALIZADO"
        $fechaActual = now()->format('Y-m-d');
        $tramitesNoFinalizados = CpuTramite::where('tra_fecha_recibido', '>=', '2024-01-01')
            ->where('tra_estado_tramite', '!=', 3)
            ->get()
            ->map(function ($tramite) use ($fechaActual) {
                $tramite->dias_desde_recibido = now()->diffInDays($tramite->tra_fecha_recibido);
                return $tramite;
            });

        return response()->json([
            'tramitesEnRango' => $tramitesEnRango,
            'tramitesNoFinalizados' => $tramitesNoFinalizados
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CpuTramite $cpuTramite)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        // Validar los datos recibidos
        $validatedData = $request->validate([
            'tra_id_persona_modifico' => 'nullable|integer',
            'tra_tipo' => 'nullable|integer',
            'tra_fecha_recibido' => 'nullable|date',
            'tra_fecha_documento' => 'nullable|date',
            'tra_num_documento' => 'nullable|max:255',
            'tra_suscrito' => 'nullable|max:255',
            'tra_direccion' => 'nullable|max:255',
            'tra_asunto' => 'nullable|max:255',
            'tra_area_derivada' => 'nullable|max:255',
            'tra_fecha_derivacion' => 'nullable|date',
            'tra_fecha_contestacion' => 'nullable|date',
            'tra_num_contestacion' => 'nullable|max:255',
            'tra_direccion_enviada' => 'nullable|max:255',
            'tra_observacion' => 'nullable|max:255',
            'tra_estado_tramite' => 'nullable|integer',
            'tra_link_receptado' => 'nullable|max:255',
            'tra_link_enviado' => 'nullable|max:255',
            'tra_cargo' => 'nullable|max:255',
            'otro_cargo' => 'nullable|max:255',
            'otra_dependencia' => 'nullable|max:255',
        ]);

        // Buscar el trámite por el ID proporcionado
        $cpuTramite = CpuTramite::find($id);

        if (!$cpuTramite) {
            return response()->json(['message' => 'Trámite no encontrado'], 404);
        }

        // Actualizar solo los campos enviados por el usuario
        $cpuTramite->update($validatedData);

        return response()->json([
            'message' => 'Trámite actualizado exitosamente',
            'data' => $cpuTramite
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $tramite = CpuTramite::find($id);
        if (!$tramite) {
            return response()->json(['message' => 'Trámite no encontrado'], 404);
        }
        $tramite->delete();
        return response()->json(['message' => 'Trámite eliminado correctamente']);
    }
}
