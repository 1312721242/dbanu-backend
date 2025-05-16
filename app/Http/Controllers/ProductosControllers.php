<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;


class ProductosControllers extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function consultarProductos()
    {
        $data = DB::select('SELECT * FROM public.view_productos');
        return response()->json($data);
    }
}
