<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class OrdenesAnalisisControllers extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function ConsultarOrdenAnalisis()
    {
        $data = DB::select('SELECT * FROM public.view_ordenes_analisis');
        return response()->json($data);
    }
}
