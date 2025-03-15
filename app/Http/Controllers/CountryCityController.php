<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class CountryCityController extends Controller
{
    //
    public function getCountries()
    {
        try {
            $response = Http::get('https://countriesnow.space/api/v0.1/countries');
    
            if ($response->successful()) {
                $countries = collect($response->json()['data'])->pluck('country'); // Extraction des pays
    
                return response()->json($countries);
            } else {
                return response()->json(['error' => 'Impossible de récupérer les pays'], 500);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erreur serveur', 'message' => $e->getMessage()], 500);
        }
    }

    public function getCities(Request $request)
    {
        $country = $request->query('country'); // Récupérer le pays depuis la requête

        if (!$country) {
            return response()->json(['error' => 'Le paramètre "country" est requis.'], 400);
        }

        try {
            $response = Http::post('https://countriesnow.space/api/v0.1/countries/cities', [
                'country' => $country
            ]);

            if ($response->successful()) {
                return response()->json($response->json()['data']);
            } else {
                return response()->json(['error' => 'Impossible de récupérer les villes'], 500);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erreur serveur', 'message' => $e->getMessage()], 500);
        }}

}
