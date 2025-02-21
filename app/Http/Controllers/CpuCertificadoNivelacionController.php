<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CpuCertificadoNivelacion;
use Illuminate\Support\Facades\DB;
class CpuCertificadoNivelacionController extends Controller
{
    public function datosCertificado(Request $request, $periodo_certificado, $sede, $carrera)
    {
        $datos = CpuCertificadoNivelacion::where('periodo_certificado', $periodo_certificado)
            ->where('sede', $sede)
            ->where('carrera', $carrera)
            ->get();

        // Auditoría
        $this->auditar('cpu_certificado_nivelacion', 'id', '', '', 'CONSULTA', "CONSULTA DE CERTIFICADO DE NIVELACION");

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
     // Función para auditar
     private function auditar($tabla, $campo, $dataOld, $dataNew, $tipo, $descripcion, $request = null)
     {
         $usuario = $request ? $request->user()->name : auth()->user()->name;
         $ip = $request ? $request->ip() : request()->ip();
         $ipv4 = gethostbyname(gethostname());
         $publicIp = file_get_contents('http://ipecho.net/plain');
         $ioConcatenadas = 'IP LOCAL: ' . $ip . '  --IPV4: ' . $ipv4 . '  --IP PUBLICA: ' . $publicIp;
         $nombreequipo = gethostbyaddr($ip);
         $userAgent = $request ? $request->header('User-Agent') : request()->header('User-Agent');
         $tipoEquipo = 'Desconocido';

         if (stripos($userAgent, 'Mobile') !== false) {
             $tipoEquipo = 'Celular';
         } elseif (stripos($userAgent, 'Tablet') !== false) {
             $tipoEquipo = 'Tablet';
         } elseif (stripos($userAgent, 'Laptop') !== false || stripos($userAgent, 'Macintosh') !== false) {
             $tipoEquipo = 'Laptop';
         } elseif (stripos($userAgent, 'Windows') !== false || stripos($userAgent, 'Linux') !== false) {
             $tipoEquipo = 'Computador de Escritorio';
         }
         $nombreUsuarioEquipo = get_current_user() . ' en ' . $tipoEquipo;

         $fecha = now();
         $codigo_auditoria = strtoupper($tabla . '_' . $campo . '_' . $tipo );
         DB::table('cpu_auditoria')->insert([
             'aud_user' => $usuario,
             'aud_tabla' => $tabla,
             'aud_campo' => $campo,
             'aud_dataold' => $dataOld,
             'aud_datanew' => $dataNew,
             'aud_tipo' => $tipo,
             'aud_fecha' => $fecha,
             'aud_ip' => $ioConcatenadas,
             'aud_tipoauditoria' => $this->getTipoAuditoria($tipo),
             'aud_descripcion' => $descripcion,
             'aud_nombreequipo' => $nombreequipo,
             'aud_descrequipo' => $nombreUsuarioEquipo,
             'aud_codigo' => $codigo_auditoria,
             'created_at' => now(),
             'updated_at' => now(),

         ]);
     }

     private function getTipoAuditoria($tipo)
     {
         switch ($tipo) {
             case 'CONSULTA':
                 return 1;
             case 'INSERCION':
                 return 3;
             case 'MODIFICACION':
                 return 2;
             case 'ELIMINACION':
                 return 4;
             case 'LOGIN':
                 return 5;
             case 'LOGOUT':
                 return 6;
             case 'DESACTIVACION':
                 return 7;
             default:
                 return 0;
         }
     }
}
