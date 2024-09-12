<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\CpuPeriodos;
use Illuminate\Routing\Controller;

class CpuPeriodosController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }


    public function consultarPeriodos()
    {
        $periodos = CpuPeriodos::orderBy('nombre', 'asc')->get();

        return response()->json($periodos);
    }

}
