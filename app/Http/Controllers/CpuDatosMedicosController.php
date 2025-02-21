<?php

namespace App\Http\Controllers;

use App\Models\CpuDatosMedicos;
use App\Models\CpuTipoSangre;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class CpuDatosMedicosController extends Controller
{
    public function index()
    {
        $datosMedicos = CpuDatosMedicos::all();
        return response()->json($datosMedicos);
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_persona' => 'nullable|exists:cpu_personas,id',
            'enfermedades_catastroficas' => 'nullable|boolean',
            'detalle_enfermedades' => 'nullable|json',
            'tipo_sangre' => 'nullable|exists:cpu_tipos_sangre,id',
            'tiene_seguro_medico' => 'nullable|boolean',
            'alergias' => 'nullable|boolean',
            'detalles_alergias' => 'nullable|json',
            'embarazada' => 'nullable|boolean',
            'meses_embarazo' => 'nullable|numeric',
            'observacion_embarazo' => 'nullable|string',
            'dependiente_medicamento' => 'nullable|boolean',
            'medicamentos_dependiente' => 'nullable|json',
            'peso' => 'nullable|numeric',
            'talla' => 'nullable|numeric',
            'imc' => 'nullable|numeric',
            'ultima_fecha_mestruacion' => 'nullable|date',
            'semanas_embarazo' => 'nullable|numeric',
            'fecha_estimada_parto' => 'nullable|date',
            'partos_data' => 'nullable|json',
        ]);

        $datosMedicos = CpuDatosMedicos::create($request->all());
        $this->auditar('cpu_datos_medicos', 'store', '', $datosMedicos, 'INSERCION', 'Creación de datos médicos');
        return response()->json($datosMedicos, 201);
    }

    public function show($id_persona)
    {
        $datosMedicos = CpuDatosMedicos::where('id_persona', $id_persona)->first();

        // Si no se encuentran datos, retornamos una respuesta vacía
        if (!$datosMedicos) {
            return response()->json(['message' => 'No se encontraron datos médicos'], 200);
        }


        return response()->json($datosMedicos);
    }


    public function update(Request $request, $id)
    {
        $datosMedicos = CpuDatosMedicos::findOrFail($id);

        $request->validate([
            'id_persona' => 'sometimes|required|exists:cpu_personas,id',
            'enfermedades_catastroficas' => 'sometimes|required|boolean',
            'detalle_enfermedades' => 'nullable|json',
            'tipo_sangre' => 'sometimes|required|exists:cpu_tipos_sangre,id',
            'tiene_seguro_medico' => 'sometimes|required|boolean',
            'alergias' => 'sometimes|required|boolean',
            'detalles_alergias' => 'nullable|json',
            'embarazada' => 'sometimes|required|boolean',
            'meses_embarazo' => 'nullable|numeric',
            'observacion_embarazo' => 'nullable|string',
            'dependiente_medicamento' => 'sometimes|required|boolean',
            'medicamentos_dependiente' => 'nullable|json',
            'peso' => 'nullable|numeric',
            'talla' => 'nullable|numeric',
            'imc' => 'nullable|numeric',
            'ultima_fecha_mestruacion' => 'nullable|date',
            'semanas_embarazo' => 'nullable|numeric',
            'fecha_estimada_parto' => 'nullable|date',
            'partos_data' => 'nullable|json',
        ]);

        $datosMedicos->update($request->all());
        $this->auditar('cpu_datos_medicos', 'update', '', $datosMedicos, 'MODIFICACION', 'Actualización de datos médicos');
        return response()->json($datosMedicos);
    }

    public function destroy($id)
    {
        $datosMedicos = CpuDatosMedicos::findOrFail($id);
        $datosMedicos->delete();
        $this->auditar('cpu_datos_medicos', 'destroy', '', $datosMedicos, 'ELIMINACION', 'Eliminación de datos médicos', $id);
        return response()->json(null, 204);
    }

    //funcion para auditar
    private function auditar($tabla, $campo, $dataOld, $dataNew, $tipo, $descripcion, $request = null)
    {
        $usuario = $request && !is_string($request) ? $request->user()->name : auth()->user()->name;
        $ip = $request && !is_string($request) ? $request->ip() : request()->ip();
        $ipv4 = gethostbyname(gethostname());
        $publicIp = file_get_contents('http://ipecho.net/plain');
        $ioConcatenadas = 'IP LOCAL: ' . $ip . '  --IPV4: ' . $ipv4 . '  --IP PUBLICA: ' . $publicIp;
        $nombreequipo = gethostbyaddr($ip);
        $userAgent = $request && !is_string($request) ? $request->header('User-Agent') : request()->header('User-Agent');
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
