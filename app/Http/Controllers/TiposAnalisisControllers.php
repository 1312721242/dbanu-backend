<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class TiposAnalisisControllers extends Controller
{
     public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function ConsultarTiposAnalisisOrion()
    {
        $ApiOrionController = new ApiOrionControllers();
        $data_ = $ApiOrionController->AOConsultarTiposAnalisis();
        $data = json_decode($data_->getContent(), true);
        if ($data['success']) {
           return response()->json($data);
        } else {
            return response()->json(['success' => false, 'message' => 'Error al consultar los tipos de análisis.'], 500);
        }
    }
}
