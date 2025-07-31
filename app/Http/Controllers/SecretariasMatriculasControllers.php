<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SecretariasMatriculasControllers extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function consultarSecretariasMatriculas()
    {
        $secretariaMatricula = DB::select('SELECT * FROM public.view_secretarias_matriculas');
        return response()->json($secretariaMatricula);
    }

}
