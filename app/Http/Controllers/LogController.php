<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon;

class LogController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function saveLog($modulo, $error)
    {
        $fechaHoraActual = now()->format('Y-m-d H:i:s');
        $response = DB::table('cpu_log')->insert([
            'l_modulo' => $modulo,
            'l_error' => $error,
            'l_created_at' =>  $fechaHoraActual,
        ]);
        return $response;
    }

    public function getLogAuditoriaErrores()
    {
        $data = DB::table('cpu_log')
            ->select('l_id', 'l_modulo', 'l_error', 'l_created_at')
            ->orderBy('l_id', 'desc')
            ->get();
        return response()->json($data, 200);
    }
}
