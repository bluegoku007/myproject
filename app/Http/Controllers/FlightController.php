<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AmadeusService;
use Illuminate\Support\Facades\Log;

class FlightController extends Controller
{
    private $amadeusService;

    public function __construct(AmadeusService $amadeusService)
    {
        $this->amadeusService = $amadeusService;
    }

    public function searchFlights(Request $request)
    {
        try {
            // Paramètres de la requête API Amadeus
            $queryParams = [
                'originLocationCode'      => $request->query('origin', 'TUN'),
                'destinationLocationCode' => $request->query('destination', 'PAR'),
                'departureDate'           => $request->query('fromDate', '2025-03-01'),
                'returnDate'              => $request->query('toDate', '2025-03-10'),
                'adults'                  => $request->query('adults', 1),
            ];
    
            Log::info("🔍 Requête envoyée à Amadeus : ", $queryParams);
    
            // Appel à l'API Amadeus via le service
            $flights = $this->amadeusService->searchFlights($queryParams);
    
            // Filter unique flights by ID
            $uniqueFlights = [];
            $seenIds = [];
    
            if (isset($flights['data'])) {
                foreach ($flights['data'] as $flight) {
                    // Use the flight ID or create a unique identifier if ID doesn't exist
                    $flightId = $flight['id'] ?? md5(json_encode([
                        $flight['itineraries'],
                        $flight['price']['total'],
                        $flight['validatingAirlineCodes'][0] ?? ''
                    ]));
    
                    if (!in_array($flightId, $seenIds)) {
                        $seenIds[] = $flightId;
                        $uniqueFlights[] = $flight;
                    }
                }
                $flights['data'] = $uniqueFlights;
            }
    
            Log::info("✅ Réponse filtrée d'Amadeus : ", ['count' => count($uniqueFlights)]);
    
            return response()->json($flights);
    
        } catch (\Exception $e) {
            Log::error("❌ Erreur lors de la récupération des vols : " . $e->getMessage());
            return response()->json([
                'error'   => 'Erreur lors de la récupération des vols',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
