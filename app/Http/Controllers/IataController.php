<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class IataController extends Controller
{
    public function getIataCode(Request $request)
    {
        $request->validate([
            'city' => 'required|string|min:2|max:50'
        ]);

        $city = $this->normalizeCityName($request->city);
        Log::info('IATA Request Initiated', ['city' => $city]);

        try {
            $iataCode = Cache::remember("iata:{$city}", now()->addDays(7), function() use ($city) {
                return $this->fetchFromAeroDataBox($city)
                    ?? $this->fetchFromGeoDBCities($city)
                    ?? $this->getFromLocalDatabase($city);
            });

            return response()->json([
                'city' => $city,
                'iata_code' => $iataCode ?: 'Not found',
                'source' => $this->getSource($city, $iataCode)
            ]);

        } catch (\Exception $e) {
            Log::error('IATA Lookup Failed', [
                'city' => $city,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'city' => $city,
                'iata_code' => $this->getFromLocalDatabase($city),
                'source' => 'local_fallback',
                'warning' => 'Using fallback data'
            ], 200);
        }
    }


    function getIataCodeFromGeoDB($capital)
    {
        $url = 'https://wft-geo-db.p.rapidapi.com/v1/geo/cities?namePrefix=' . urlencode($capital);
        $headers = [
            'X-RapidAPI-Key: 00ef5f6ef2msh0debe32e4909d94p18da34jsn3d7e025b69c7',
            'X-RapidAPI-Host: wft-geo-db.p.rapidapi.com',
        ];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $data = json_decode($response, true);
        
        if (isset($data['data']) && count($data['data']) > 0) {
            // Check and return the IATA code for the capital
            foreach ($data['data'] as $city) {
                if (strtolower($city['city']) == strtolower($capital)) {
                    return $city['iata_code']; // Assuming the IATA code is available in the response
                }
            }
        }
        
        return null; // No IATA code found
    }
    


    public function getCapitalIata(Request $request)
    {
        $request->validate([
            'country' => 'required|string|min:2'
        ]);

        $country = $this->normalizeCityName($request->country);
        Log::info('Capital IATA Request Initiated', ['country' => $country]);

        try {
            $iataCode = Cache::remember("capital_iata:{$country}", now()->addDays(7), function() use ($country) {
                // 1. Try AeroDataBox first
                if ($code = $this->fetchCapitalAirport($country)) {
                    return $code;
                }

                // 2. Fallback to GeoDB
                if ($code = $this->fetchCapitalFromGeoDB($country)) {
                    return $code;
                }

                // 3. Final fallback to local database
                return $this->getCapitalFromLocalDatabase($country);
            });

            return response()->json([
                'country' => $country,
                'iata_code' => $iataCode ?: 'Not found',
                'source' => $this->getCapitalSource($country, $iataCode)
            ]);

        } catch (\Exception $e) {
            Log::error('Capital IATA Lookup Failed', [
                'country' => $country,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'country' => $country,
                'iata_code' => 'Not found',
                'source' => 'api_fallback',
                'warning' => 'Could not determine capital IATA code'
            ], 200);
        }
    }

    protected function fetchFromAeroDataBox(string $city)
    {
        $apiKey = env('AERODATABOX_API_KEY');
        if (empty($apiKey)) {
            Log::error('AeroDataBox API key not configured');
            return null;
        }

        $response = Http::timeout(10)
            ->withHeaders([
                'X-RapidAPI-Key' => $apiKey,
                'X-RapidAPI-Host' => 'aerodatabox.p.rapidapi.com'
            ])
            ->get('https://aerodatabox.p.rapidapi.com/airports/search/term', [
                'q' => $city,
                'limit' => 1
            ]);

        if (!$response->successful()) {
            throw new \Exception("AeroDataBox API error: {$response->status()}");
        }

        $data = $response->json();
        
        if (empty($data['items'])) {
            Log::debug('AeroDataBox returned empty results', ['city' => $city]);
            return null;
        }

        $iataCode = $data['items'][0]['iata'] ?? null;
        if ($iataCode) {
            $this->cacheLocally(
                $city,
                $iataCode,
                $data['items'][0]['country']['name'] ?? null,
                $data['items'][0]['municipalityName'] ?? null
            );
        }

        return $iataCode;
    }

    protected function fetchCapitalAirport(string $country)
    {
        $apiKey = env('AERODATABOX_API_KEY');
        if (empty($apiKey)) return null;

        try {
            $response = Http::timeout(8)
                ->withHeaders([
                    'X-RapidAPI-Key' => $apiKey,
                    'X-RapidAPI-Host' => 'aerodatabox.p.rapidapi.com'
                ])
                ->get('https://aerodatabox.p.rapidapi.com/airports/search/term', [
                    'q' => $country,
                    'limit' => 5
                ]);

            if ($response->successful()) {
                $data = $response->json();
                
                // Look for capital airports first
                foreach ($data['items'] ?? [] as $airport) {
                    if (stripos($airport['name'] ?? '', 'capital') !== false) {
                        return $airport['iata'] ?? null;
                    }
                }
                
                // Fallback to first major airport
                return $data['items'][0]['iata'] ?? null;
            }
        } catch (\Exception $e) {
            Log::error('AeroDataBox API Error', ['error' => $e->getMessage()]);
        }

        return null;
    }

    protected function fetchFromGeoDBCities(string $city)
    {
        $apiKey = env('GEO_DB_API_KEY');
        if (empty($apiKey)) {
            Log::error('GeoDB API key not configured');
            return null;
        }

        $response = Http::timeout(10)
            ->withHeaders([
                'X-RapidAPI-Key' => $apiKey,
                'X-RapidAPI-Host' => 'wft-geo-db.p.rapidapi.com'
            ])
            ->get('https://wft-geo-db.p.rapidapi.com/v1/geo/cities', [
                'namePrefix' => $city,
                'limit' => 5,
                'sort' => '-population'
            ]);

        if (!$response->successful()) {
            throw new \Exception("GeoDB API error: {$response->status()}");
        }

        $data = $response->json();
        
        if (empty($data['data'])) {
            Log::debug('GeoDB returned empty results', ['city' => $city]);
            return null;
        }

        foreach ($data['data'] as $location) {
            if (!empty($location['code'])) {
                $this->cacheLocally(
                    $city,
                    $location['code'],
                    $location['country'] ?? null,
                    $location['name'] ?? null
                );
                return $location['code'];
            }
        }

        return null;
    }

    protected function fetchCapitalFromGeoDB(string $country)
    {
        $apiKey = env('GEO_DB_API_KEY');
        if (empty($apiKey)) return null;

        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'X-RapidAPI-Key' => $apiKey,
                    'X-RapidAPI-Host' => 'wft-geo-db.p.rapidapi.com'
                ])
                ->get('https://wft-geo-db.p.rapidapi.com/v1/geo/countries', [
                    'namePrefix' => $country,
                    'limit' => 1
                ]);

            if ($response->successful()) {
                $data = $response->json();
                if (!empty($data['data'][0]['capital'])) {
                    return $this->fetchFromGeoDBCities($data['data'][0]['capital']);
                }
            }
        } catch (\Exception $e) {
            Log::error('GeoDB Capital Lookup Error', ['error' => $e->getMessage()]);
        }

        return null;
    }

    protected function cacheLocally(string $city, string $iataCode, ?string $country, ?string $fullName)
    {
        DB::table('local_iata_codes')->updateOrInsert(
            ['city' => $city],
            [
                'iata_code' => $iataCode,
                'country' => $country,
                'full_name' => $fullName,
                'updated_at' => now()
            ]
        );
        Cache::put("iata_local:{$city}", true, now()->addDays(30));
    }

    protected function getFromLocalDatabase(string $city)
    {
        try {
            return DB::table('local_iata_codes')
                ->where('city', 'like', "{$city}%")
                ->orderBy('city')
                ->value('iata_code')
                ?? $this->commonIataCodes[$city] 
                ?? null;
        } catch (\Exception $e) {
            Log::error('Local DB Error', ['error' => $e->getMessage()]);
            return $this->commonIataCodes[$city] ?? null;
        }
    }

    protected function getCapitalFromLocalDatabase(string $country)
    {
        $capitals = [
            'tunisia' => 'TUN',
            'france' => 'PAR',
            'germany' => 'BER',
            'united states' => 'WAS',
            'united kingdom' => 'LON',
            'australia' => 'SYD',
            'japan' => 'TYO',
            'spain' => 'MAD',
            'italy' => 'ROM',
            'russia' => 'SVO',
            'china' => 'PEK',
            'algeria' => 'ALG'
        ];

        return $capitals[$country] ?? null;
    }

    protected function getSource(string $city, ?string $iataCode): ?string
    {
        if (!$iataCode) return null;
        
        if (Cache::has("iata_local:{$city}")) {
            return 'local_cache';
        }
        
        if (isset($this->commonIataCodes[$city])) {
            return 'hardcoded';
        }
        
        try {
            $cached = Cache::get("iata:{$city}");
            if (str_contains($cached, 'aerodatabox')) {
                return 'aerodatabox';
            }
        } catch (\Exception $e) {
            Log::debug('Source detection error', ['error' => $e->getMessage()]);
        }
        
        return 'geodb';
    }

    protected function getCapitalSource(string $country, ?string $iataCode): ?string
    {
        if (!$iataCode || $iataCode === 'Not found') return null;
        
        try {
            $cached = Cache::get("capital_iata:{$country}");
            
            if (str_contains($cached, 'aerodatabox')) {
                return 'aerodatabox';
            }
            
            if (str_contains($cached, 'geodb')) {
                return 'geodb';
            }
        } catch (\Exception $e) {
            Log::debug('Source detection error', ['error' => $e->getMessage()]);
        }
        
        return $this->getCapitalFromLocalDatabase($country) === $iataCode 
            ? 'hardcoded' 
            : 'api';
    }

    protected function normalizeCityName(string $city): string
    {
        $normalized = iconv('UTF-8', 'ASCII//TRANSLIT', $city);
        $normalized = preg_replace('/[^a-zA-Z ]/', '', $normalized);
        return strtolower(trim($normalized));
    }

    protected $commonIataCodes = [
        'tunis' => 'TUN',
        'paris' => 'PAR',
        'london' => 'LHR',
        'new york' => 'JFK',
        'berlin' => 'BER',
        'tokyo' => 'TYO',
        'dubai' => 'DXB',
        'rome' => 'FCO',
        'moscow' => 'SVO',
    ];
}