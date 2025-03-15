<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class CurrencyController extends Controller
{
    public function getCurrencies()
    {
        $apiKey = env('EXCHANGE_RATE_API_KEY'); // Stocke la clé dans le fichier .env
        $url = "https://v6.exchangerate-api.com/v6/{$apiKey}/latest/USD";

        $response = Http::get($url);

        if ($response->failed()) {
            return response()->json(['error' => 'Erreur lors de la récupération des taux de change'], 500);
        }

        return response()->json($response->json()['conversion_rates']);
    }
}
