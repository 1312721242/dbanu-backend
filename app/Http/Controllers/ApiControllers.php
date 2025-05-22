<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class ApiControllers extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }
    
    public function loginToken()
{
    $client = new Client();

    $url = 'https://demo.orion-labs.com/api/v1/examenes';  

    $token = 'bQ2i2NlToNFmU4Z3uKDONpBtJEcUOKMvAWKPLijLX1DgP0WbPT8IvDZVswpn';

    try {
        $response = $client->request('GET', $url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,  
                'Accept' => 'application/json',  
            ]
        ]);

        $responseBody = $response->getBody();
        $data = json_decode($responseBody, true);

        return response()->json($data);

    } catch (RequestException $e) {
        return response()->json([
            'error' => $e->getMessage()
        ], 400);
    }
}


 public function  ApiConsultarTiposAnalisis()
    {
        $token = '';
        $objeto_Api = new ApiControllers();
        $data_token = $objeto_Api->loginToken();

        return "hola";
    }

/**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
