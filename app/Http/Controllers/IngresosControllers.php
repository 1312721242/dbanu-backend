<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class IngresosControllers extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function consultarIngresos()
    {
        $data = DB::select('SELECT * FROM public.view_ingresos');
        return response()->json($data);
    }

}
