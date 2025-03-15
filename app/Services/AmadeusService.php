<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AmadeusService
{
    private $apiUrl;
    private $clientId;
    private $clientSecret;

    public function __construct()
    {
        $this->apiUrl = env('AMADEUS_API_URL', 'https://test.api.amadeus.com');
        $this->clientId = env('AMADEUS_API_KEY');
        $this->clientSecret = env('AMADEUS_API_SECRET');
    }

    // Obtenir un token d'accès
    private function getAccessToken()
    {
        Log::info("Demande de token d'accès Amadeus...");

        $response = Http::asForm()->post("{$this->apiUrl}/v1/security/oauth2/token", [
            'grant_type'    => 'client_credentials',
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
        ]);

        if ($response->failed()) {
            Log::error("Échec de l'obtention du token Amadeus : " . $response->body());
            throw new \Exception("Failed to retrieve access token from Amadeus");
        }

        return $response->json()['access_token'];
    }

    // Chercher des vols
    public function searchFlights($queryParams)
    {
        $accessToken = $this->getAccessToken();

        Log::info("Token d'accès reçu : " . $accessToken);

        $response = Http::withToken($accessToken)->get("{$this->apiUrl}/v2/shopping/flight-offers", $queryParams);

        if ($response->failed()) {
            Log::error("Erreur API Amadeus : " . $response->body());
            throw new \Exception("Erreur lors de la récupération des vols");
        }

        return $response->json();
    }
}
