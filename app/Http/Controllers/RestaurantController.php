<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RestaurantController extends Controller
{
    public function getRestaurants(Request $request)
    {
        $request->validate([
            'city' => 'required|string',
        ]);

        $city = $request->input('city');
        $foursquareApiKey = env('FOURSQUARE_API_KEY');

        // 🔹 Étape 1: Obtenir les coordonnées de la ville via OpenStreetMap (Nominatim)
        $geoResponse = Http::withHeaders([
            'User-Agent' => 'MyLaravelApp/1.0 (your@email.com)' // Important pour éviter le blocage
        ])->get("https://nominatim.openstreetmap.org/search", [
            'q' => $city,
            'format' => 'json',
            'limit' => 1
        ]);

        $geoData = $geoResponse->json();

        // 🔹 Log de la réponse pour debug
        Log::info("Nominatim Response: " . json_encode($geoData));

        if (empty($geoData)) {
            return response()->json(['error' => 'City not found'], 404);
        }

        $latitude = $geoData[0]['lat'];
        $longitude = $geoData[0]['lon'];

        // 🔹 Étape 2: Rechercher les restaurants via Foursquare
        $response = Http::withHeaders([
            'Authorization' => $foursquareApiKey
        ])->get("https://api.foursquare.com/v3/places/search", [
            'll' => "$latitude,$longitude",
            'categories' => '13065', // ID de catégorie pour les restaurants
            'radius' => 5000, // Rayon de recherche en mètres
            'limit' => 10
        ]);

        $foursquareData = $response->json();

        // 🔹 Log de la réponse de Foursquare
        Log::info("Foursquare Response: " . json_encode($foursquareData));

        // Vérifier si la réponse contient des résultats
        if (!isset($foursquareData['results']) || empty($foursquareData['results'])) {
            return response()->json(['error' => 'No restaurants found'], 404);
        }

        return response()->json($foursquareData);
    }
}
