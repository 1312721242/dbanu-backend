<?php

namespace App\Http\Controllers;

use App\Models\CpuTipoBeca;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class CpuTipoBecaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $tiposBeca = CpuTipoBeca::where('id_estado', 8)->get();
        return response()->json($tiposBeca);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $validatedData = $request->validate([
            'nombre_beca' => 'required|string',
            'nivel' => 'required|string',
            'id_estado' => 'nullable|integer'
        ]);

        $tipoBeca = CpuTipoBeca::create([
            'nombre_beca' => $validatedData['nombre_beca'],
            'nivel' => $validatedData['nivel'],
            'id_estado' => $validatedData['id_estado'] ?? 8,
            'created_at' => now(),
            'updated_at' => now()
        ]);
        $this->auditar('cpu_tipo_beca', 'create', '', $tipoBeca, 'INSERCION', 'Creación de tipo de beca');

        return response()->json($tipoBeca, 201);
    }


    public function show()
    {
        $tiposBeca = CpuTipoBeca::where('id_estado', 8)->get();
        $this->auditar('cpu_tipo_beca', 'show', '', $tiposBeca, 'CONSULTA', 'Consulta de tipos de becas');
        return response()->json($tiposBeca);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CpuTipoBeca $cpuTipoBeca)
    {
        //cambiar el estado a  8 o 9 dependiendo de lo qye tenga en la base de datos
        $tipoBeca = CpuTipoBeca::find($cpuTipoBeca->id);
        $tipoBeca->id_estado = $tipoBeca->id_estado == 8 ? 9 : 8;
        $tipoBeca->save();
        $this->auditar('cpu_tipo_beca', 'edit', '', $tipoBeca, 'MODIFICACION', 'Modificación de tipo de beca', $cpuTipoBeca);
        return response()->json($tipoBeca);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, CpuTipoBeca $cpuTipoBeca)
    {
        //actualizar uno o varios campos de la tabla cpu_tipo_beca
        $tipoBeca = CpuTipoBeca::find($cpuTipoBeca->id);
        $tipoBeca->update($request->all());
        $this->auditar('cpu_tipo_beca', 'update', '', $tipoBeca, 'MODIFICACION', 'Modificación de tipo de beca', $cpuTipoBeca);
        return response()->json($tipoBeca);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CpuTipoBeca $cpuTipoBeca)
    {
        $tipoBeca = CpuTipoBeca::find($cpuTipoBeca->id);
        $tipoBeca->delete();
        $this->auditar('cpu_tipo_beca', 'destroy', '', $tipoBeca, 'ELIMINACION', 'Eliminación de tipo de beca', $cpuTipoBeca);
        return response()->json($tipoBeca);
    }

    //funcion para auditar
    private function auditar($tabla, $campo, $dataOld, $dataNew, $tipo, $descripcion, $request = null)
    {
        $usuario = $request && !is_string($request) ? $request->user()->name : auth()->user()->name;
        $ip = $request && !is_string($request) ? $request->ip() : request()->ip();
        $ipv4 = gethostbyname(gethostname());
        $publicIp = file_get_contents('https://ifconfig.me/ip');
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
