<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use GuzzleHttp\Client;

class ApiControllers extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }
    
    public function ApiConsultarTiposAnalisis()
    {
        $client = new Client();
        $allData = [];
        $nextPageUrl = 'https://demo.orion-labs.com/api/v1/examenes?pagina=1';
    
        try {
            while ($nextPageUrl) {
                $response = $client->request('GET', $nextPageUrl, [
                    'headers' => [
                        'accept' => 'application/json',
                        'Authorization' => 'Bearer bQ2i2NlToNFmU4Z3uKDONpBtJEcUOKMvAWKPLijLX1DgP0WbPT8IvDZVswpn',
                    ],
                ]);
    
                $data = json_decode($response->getBody(), true);
    
                $allData = array_merge($allData, $data['data']);
                $nextPageUrl = $data['links']['next'] ?? null;
            }
    
            $allDataConId = array_map(function ($item, $index) {
                $item['id'] = $index + 1;
                return $item;
            }, $allData, array_keys($allData));
    
            return response()->json([
                'success' => true,
                'data' => $allDataConId,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar todas las páginas.',
                'error' => $e->getMessage(),
            ], 500);
        }
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
