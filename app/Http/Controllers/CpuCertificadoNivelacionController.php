<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CpuCertificadoNivelacion;

class CpuCertificadoNivelacionController extends Controller
{
    public function datosCertificado(Request $request, $periodo_certificado, $sede, $carrera)
    {
        $datos = CpuCertificadoNivelacion::where('periodo_certificado', $periodo_certificado)
            ->where('sede', $sede)
            ->where('carrera', $carrera)
            ->get();

        // Esto siempre será un arreglo, incluso si está vacío
        return response()->json($datos);
    }


    public function getSedes()
    {
        $sedes = CpuCertificadoNivelacion::select('sede')->distinct()->get();

        return response()->json($sedes);
    }

    public function getCarrerasBySede(Request $request, $sede)
    {
        $carreras = CpuCertificadoNivelacion::where('sede', $sede)
            ->select('carrera')
            ->distinct()
            ->get();

        return response()->json($carreras);
    }

    public function getSedesByPeriodo(Request $request, $periodo_certificado)
    {
        $carreras = CpuCertificadoNivelacion::where('periodo_certificado', $periodo_certificado)
            ->select('sede')
            ->distinct()
            ->get();

        return response()->json($carreras);
    }

    public function getCarrerasByPeriodoAndSede(Request $request, $periodo_certificado, $sede)
    {
        $carreras = CpuCertificadoNivelacion::where('periodo_certificado', $periodo_certificado)
            ->where('sede', $sede)
            ->select('carrera')
            ->distinct()
            ->get();

        return response()->json($carreras);
    }
}
