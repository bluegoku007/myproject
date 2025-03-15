<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class GeolocationController extends Controller
{
    public function getCountry(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        $latitude = $request->latitude;
        $longitude = $request->longitude;

        // Appel à l'API BigDataCloud
        $response = Http::get('https://api.bigdatacloud.net/data/reverse-geocode-client', [
            'latitude' => $latitude,
            'longitude' => $longitude,
            'localityLanguage' => 'en',
        ]);

        if ($response->successful()) {
            $data = $response->json();
            return response()->json([
                'country' => $data['countryName'] ?? 'Unknown',
            ]);
        } else {
            return response()->json(['error' => 'Impossible de récupérer la localisation'], 500);
        }
    }
}
