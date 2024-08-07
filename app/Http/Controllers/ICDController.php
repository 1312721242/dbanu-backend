<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ICDController extends Controller
{
    private $clientId;
    private $clientSecret;

    public function __construct()
    {
        $this->clientId = env('ICD_API_CLIENT_ID');
        $this->clientSecret = env('ICD_API_CLIENT_SECRET');
    }

    public function getToken()
    {
        try {
            $response = Http::asForm()->withHeaders([
                'Authorization' => 'Basic ' . base64_encode($this->clientId . ':' . $this->clientSecret),
                'Content-Type' => 'application/x-www-form-urlencoded',
            ])->post('https://icdaccessmanagement.who.int/connect/token', [
                'grant_type' => 'client_credentials',
                'scope' => 'icdapi_access',
            ]);

            if ($response->successful()) {
                return response()->json($response->json());
            } else {
                return response()->json(['error' => 'Failed to fetch token'], 500);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function searchICD(Request $request)
    {
        $token = $request->input('token');
        $query = $request->input('query');

        try {
            $response = Http::withToken($token)
                ->withHeaders([
                    'Accept-Language' => 'es',
                    'API-Version' => 'v2',
                    'Accept' => 'application/json',
                ])
                ->get('https://id.who.int/icd/entity/search', [
                    'q' => $query,
                    'flatResults' => 'true',
                ]);

            if ($response->successful()) {
                $data = $response->json();
                // dd($data);  // Esta línea imprimirá y detendrá la ejecución para que puedas ver todos los campos
                $results = collect($data['destinationEntities'])->map(function($entity) {
                    return [
                        'code' => $entity['theCode'] ?? null,
                        'title' => isset($entity['title']) ? strip_tags($entity['title']) : null,
                    ];
                });

                return response()->json($results);
            } else {
                return response()->json(['error' => 'Failed to fetch ICD data'], 500);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
