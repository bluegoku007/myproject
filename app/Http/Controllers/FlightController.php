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

            Log::info("✅ Réponse reçue d'Amadeus : ", ['flights' => $flights]);

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
