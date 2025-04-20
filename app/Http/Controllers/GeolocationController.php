<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class GeolocationController extends Controller
{
    public function getCountry(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        try {
            $countryInfo = $this->getCountryFromCoordinates(
                $request->latitude,
                $request->longitude
            );

            return response()->json([
                'country' => $countryInfo['name'],
                'country_code' => $countryInfo['code'],
                'continent' => $countryInfo['continent'],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Location service unavailable',
                'message' => $e->getMessage()
            ], 500);
        }
    }

   // app/Http/Controllers/GeolocationController.php

// In GeolocationController.php

    public function getCapitalFromCoordinates(Request $request)
{
    $validated = $request->validate([
        'latitude' => 'required|numeric|between:-90,90',
        'longitude' => 'required|numeric|between:-180,180',
    ]);

    try {
        // First try BigDataCloud
        $response = Http::withHeaders([
            'User-Agent' => config('app.name')
        ])->get('https://api.bigdatacloud.net/data/reverse-geocode-client', [
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'localityLanguage' => 'en',
            'key' => env('BIGDATACLOUD_API_KEY'), // Add if you have one
        ]);

        if (!$response->successful()) {
            // Fallback to OpenStreetMap
            $response = Http::get('https://nominatim.openstreetmap.org/reverse', [
                'format' => 'json',
                'lat' => $validated['latitude'],
                'lon' => $validated['longitude'],
                'zoom' => 3, // Country level
                'accept-language' => 'en'
            ]);
        }

        $geoData = $response->json();
        
        $countryName = $this->cleanCountryName(
            $geoData['countryName'] ?? 
            $geoData['address']['country'] ?? 
            'Unknown'
        );

        $capital = $this->getCapitalFromCountryName($countryName);

        return response()->json([
            'country' => $countryName,
            'capital' => $capital,
            'coordinates' => [
                'latitude' => $validated['latitude'],
                'longitude' => $validated['longitude']
            ]
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Could not determine location',
            'message' => $e->getMessage(),
            'fallback_data' => [
                'country' => 'Tunisia',
                'capital' => 'Tunis'
            ]
        ], 200); // Still return 200 but with error info
    }
}

    protected function getCountryFromCoordinates($latitude, $longitude)
    {
        $cacheKey = "geo_country_{$latitude}_{$longitude}";

        return Cache::remember($cacheKey, now()->addHours(6), function() use ($latitude, $longitude) {
            $response = Http::withHeaders([
                'User-Agent' => config('app.name')
            ])->get('https://api.bigdatacloud.net/data/reverse-geocode-client', [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'localityLanguage' => 'en',
            ]);

            if (!$response->successful()) {
                throw new \Exception('Geocoding service failed');
            }

            $data = $response->json();

            return [
                'name' => $this->cleanCountryName($data['countryName']),
                'code' => $data['countryCode'] ?? null,
                'continent' => $data['continent'] ?? null
            ];
        });
    }

    protected function getCapitalFromCountryName($countryName)
    {
        $cacheKey = "capital_" . md5($countryName);

        return Cache::remember($cacheKey, now()->addDays(30), function() use ($countryName) {
            // First try CountriesNow API
            try {
                $response = Http::post('https://countriesnow.space/api/v0.1/countries/capital', [
                    'country' => $countryName
                ]);

                if ($response->successful() && $data = $response->json()) {
                    return $data['data']['capital'] ?? null;
                }
            } catch (\Exception $e) {
                // Fall through to backup method
            }

            // Fallback to RestCountries API
            $response = Http::get("https://restcountries.com/v3.1/name/{$countryName}");
            
            if ($response->successful() && $data = $response->json()) {
                return $data[0]['capital'][0] ?? null;
            }

            return 'Unknown';
        });
    }

    protected function cleanCountryName($rawName)
    {
        $mapping = [
            'United Kingdom of Great Britain and Northern Ireland' => 'United Kingdom',
            'United States of America' => 'United States',
            'Korea (Republic of)' => 'South Korea',
            'Russian Federation' => 'Russia',
            'Viet Nam' => 'Vietnam',
            'Iran (Islamic Republic of)' => 'Iran',
            'Syrian Arab Republic' => 'Syria',
            'Bolivia (Plurinational State of)' => 'Bolivia',
            'Venezuela (Bolivarian Republic of)' => 'Venezuela',
            'Tanzania, United Republic of' => 'Tanzania',
            'Congo (Democratic Republic of the)' => 'DR Congo',
            'Côte d\'Ivoire' => 'Ivory Coast',
            'Myanmar' => 'Myanmar (Burma)',
            'Czechia' => 'Czech Republic',
        ];

        // Remove any parenthetical parts
        $cleaned = preg_replace('/\s*\(.*?\)$/', '', $rawName);

        return $mapping[$rawName] ?? $mapping[$cleaned] ?? $cleaned;
    }
}