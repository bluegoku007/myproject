<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class MuseumController extends Controller
{
    public function index(Request $request)
    {
        $city = $request->query('city', 'Monastir'); // Par défaut, Monastir
        $latitude = $request->query('latitude', '35.7708');
        $longitude = $request->query('longitude', '10.8281');
        $radius = 5000; // 5km de rayon
        $categoryId = '10027'; // ID catégorie Foursquare pour les musées

        $apiUrl = "https://api.foursquare.com/v3/places/search";
        $response = Http::withHeaders([
            'Authorization' => env('FOURSQUARE_API_KEY'),
            'Accept' => 'application/json'
        ])->get($apiUrl, [
            'll' => "$latitude,$longitude",
            'radius' => $radius,
            'categories' => $categoryId,
            'limit' => 10
        ]);

        return response()->json($response->json());
    }
}
