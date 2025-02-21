<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CpuDatosSociales;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class CpuDatosSocialesController extends Controller
{

    // funcion para consultar datos sociales por id_persona
    public function show($id_persona)
{
    $datosSociales = CpuDatosSociales::where('id_persona', $id_persona)->first();

    if ($datosSociales) {
        $datosSociales->estructura_estudiante = json_decode($datosSociales->estructura_estudiante, true);
        $datosSociales->servicios_basicos_estudiante = json_decode($datosSociales->servicios_basicos_estudiante, true);
        $datosSociales->estructura_familia = json_decode($datosSociales->estructura_familia, true);
        $datosSociales->servicios_basicos_familia = json_decode($datosSociales->servicios_basicos_familia, true);
        $datosSociales->ingresos = json_decode($datosSociales->ingresos, true);
        $datosSociales->egresos = json_decode($datosSociales->egresos, true);
        $datosSociales->markers = json_decode($datosSociales->markers, true);
    }
    $this->auditar('cpu_datos_sociales', 'show', '', $datosSociales, 'CONSULTA', 'Consulta de datos sociales', $id_persona);

    return response()->json($datosSociales);
}

    public function store(Request $request)
    {
        // dd($request);
        $validated = $request->validate([

            'id_persona' => 'required|integer', // Verifica que el id_persona sea un entero válido y exista en la tabla
            'situacion_estudiante' => 'nullable|string',
            'dormitorios_estudiante' => 'nullable|integer',
            'tipo_vivienda_estudiante' => 'nullable|string',
            'estructura_estudiante' => 'nullable|json',
            'servicios_basicos_estudiante' => 'nullable|json',
            'situacion_familia' => 'nullable|string',
            'dormitorios_familia' => 'nullable|integer',
            'tipo_vivienda_familia' => 'nullable|string',
            'estructura_familia' => 'nullable|json',
            'servicios_basicos_familia' => 'nullable|json',
            'problema_salud' => 'nullable|string',
            'diagnostico' => 'nullable|string',
            'parentesco' => 'nullable|string',
            'ingresos' => 'nullable|json',
            'egresos' => 'nullable|json',
            'diferencia' => 'nullable|numeric',
            'markers' => 'nullable|json',
            // 'image' => 'nullable|file|image|max:2048',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('genogramas', 'public');
        }

        $datosSociales = CpuDatosSociales::create(array_merge($validated, ['image_path' => $imagePath]));
        $this->auditar('cpu_datos_sociales', 'store', '', $datosSociales, 'INSERCION', 'Creación de datos sociales', $request);

        return response()->json(['message' => 'Datos sociales guardados exitosamente', 'data' => $datosSociales]);
    }

    public function updateByPersonaId(Request $request)
    {
        $validated = $request->validate([
            'id_persona' => 'required|integer',
            'situacion_estudiante' => 'nullable|string',
            'dormitorios_estudiante' => 'nullable|integer',
            'tipo_vivienda_estudiante' => 'nullable|string',
            'estructura_estudiante' => 'nullable|json',
            'servicios_basicos_estudiante' => 'nullable|json',
            'situacion_familia' => 'nullable|string',
            'dormitorios_familia' => 'nullable|integer',
            'tipo_vivienda_familia' => 'nullable|string',
            'estructura_familia' => 'nullable|json',
            'servicios_basicos_familia' => 'nullable|json',
            'problema_salud' => 'nullable|boolean',
            'diagnostico' => 'nullable|string',
            'parentesco' => 'nullable|string',
            'ingresos' => 'nullable|json',
            'egresos' => 'nullable|json',
            'diferencia' => 'nullable|numeric',
            'markers' => 'nullable|json',
            'image' => 'nullable|file|image|max:2048',
        ]);

        $datosSociales = CpuDatosSociales::where('id_persona', $validated['id_persona'])->firstOrFail();

        if ($request->hasFile('image')) {
            if ($datosSociales->image_path) {
                Storage::delete($datosSociales->image_path);
            }
            $validated['image_path'] = $request->file('image')->store('genogramas', 'public');
        }

        $datosSociales->update($validated);
        $this->auditar('cpu_datos_sociales', 'updateByPersonaId', '', $datosSociales, 'MODIFICACION', 'Actualización de datos sociales', $request);

        return response()->json(['message' => 'Datos sociales actualizados exitosamente', 'data' => $datosSociales]);
    }

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
