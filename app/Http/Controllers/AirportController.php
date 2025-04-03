<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AirportController extends Controller
{
    public function getAirportCodes(Request $request)
    {
        // Vérifier si le paramètre "city" est fourni
        $city = $request->input('city');
        if (!$city) {
            return response()->json(['error' => 'La ville est requise'], 400);
        }

        // Récupérer le token d'authentification Amadeus
        $apiKey = env('AMADEUS_API_KEY');
        $apiSecret = env('AMADEUS_API_SECRET');

        $tokenResponse = Http::asForm()->post('https://test.api.amadeus.com/v1/security/oauth2/token', [
            'grant_type' => 'client_credentials',
            'client_id' => $apiKey,
            'client_secret' => $apiSecret,
        ]);

        if ($tokenResponse->failed()) {
            return response()->json(['error' => 'Erreur lors de l’authentification Amadeus'], 500);
        }

        $accessToken = $tokenResponse->json()['access_token'];

        // Appel API Amadeus pour récupérer les aéroports
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
        ])->get('https://test.api.amadeus.com/v1/reference-data/locations', [
            'keyword' => $city,
            'subType' => 'AIRPORT',
        ]);

        if ($response->failed()) {
            return response()->json(['error' => 'Erreur lors de la récupération des aéroports'], 500);
        }

        $airports = $response->json()['data'];

        if (empty($airports)) {
            return response()->json(['error' => 'Aucun aéroport trouvé pour cette ville'], 404);
        }

        // Formater la réponse pour inclure tous les aéroports trouvés
        $formattedAirports = collect($airports)->map(function ($airport) {
            return [
                'airport_code' => $airport['iataCode'],
                'airport_name' => $airport['name'],
                'city' => $airport['address']['cityName'] ?? '',
                'country' => $airport['address']['countryName'] ?? '',
            ];
        });

        return response()->json([
            'city' => $city,
            'airports' => $formattedAirports,
        ]);
    }
}
