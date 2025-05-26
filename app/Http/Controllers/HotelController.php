<?php
namespace App\Http\Controllers;

use App\Models\Hotel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class HotelController extends Controller 
{



    public function getHotels(Request $request)
    {
        $params = $request->only([
            'dest_id', 'search_type', 'adults', 'children_age', 'room_qty', 'page_number',
            'units', 'temperature_unit', 'languagecode', 'currency_code', 'location',
            'arrival_date', 'departure_date' // ⬅️ Ajout ici
        ]);
    
        $response = Http::withHeaders([
            'x-rapidapi-host' => env('RAPIDAPI_HOST'),
            'x-rapidapi-key' => env('RAPIDAPI_KEY'),
        ])->get('https://booking-com15.p.rapidapi.com/api/v1/hotels/searchHotels', $params);
    
        if ($response->successful()) {
            $data = $response->json();
    
            if (isset($data['data']['hotels'])) {
                return response()->json($data['data']['hotels']);
            } else {
                \Log::error('Unexpected response structure:', $data);
                return response()->json([
                    'error' => 'Unexpected API response structure',
                    'raw' => $data
                ], 502);
            }
        } else {
            return response()->json(['error' => 'Unable to fetch hotel data', 'details' => $response->body()], 500);
        }
    }
    
    
    
    public function searchDestination(Request $request)
    {
        $query = $request->query('query');
    
        try {
            $response = Http::withHeaders([
                'x-rapidapi-host' => env('RAPIDAPI_HOST'),
                'x-rapidapi-key' => env('RAPIDAPI_KEY'),
            ])->get('https://booking-com15.p.rapidapi.com/api/v1/hotels/searchDestination', [
                'query' => $query
            ]);
    
            if ($response->failed()) {
                \Log::error('RapidAPI response error:', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return response()->json(['error' => 'API request failed'], $response->status());
            }
    
            $data = $response->json();
    
            // Recherche du premier résultat de type 'city'
            $city = collect($data['data'] ?? [])->firstWhere('search_type', 'city');
    
            if ($city) {
                return response()->json([
                    'dest_id' => $city['dest_id'],
                ]);
            } else {
                return response()->json(['message' => 'No city found'], 404);
            }
    
        } catch (\Exception $e) {
            \Log::error('Exception: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to search destination'], 500);
        }
    }
    
    
    public function store(Request $request)
    {
        $validated = $request->validate([
            'hotel_id' => 'required|string',
            'name' => 'required|string',
            'city' => 'required|string',
            'country' => 'required|string',
            'check_in' => 'required|date',
            'check_out' => 'required|date',
            'price' => 'required|numeric',
            'currency' => 'required|string',
            'details' => 'required|json',
            'photo_url' => 'required|string', // Add validation for the first photo URL
        ]);
    
        $user = auth()->user();
    
        $hotel = new Hotel();
        $hotel->user_id = $user->id;
        $hotel->hotel_id = $validated['hotel_id'];
        $hotel->name = $validated['name'];
        $hotel->city = $validated['city'];
        $hotel->country = $validated['country'];
        $hotel->check_in = $validated['check_in'];
        $hotel->check_out = $validated['check_out'];
        $hotel->price = $validated['price'];
        $hotel->currency = $validated['currency'];
        $hotel->details = $validated['details'];
        $hotel->photo_url = $validated['photo_url']; // Save the first photo URL
        $hotel->save();
    
        return response()->json(['message' => 'Hotel saved successfully!', 'hotel' => $hotel], 201);
    }


    public function getHotelsByUser(Request $request, $userId)
    {
        // Fetch hotels saved by the specified user
        $hotels = Hotel::where('user_id', $userId)
                       ->orderBy('created_at', 'desc')
                       ->get();
    
        // Check if the user has saved any hotels
        if ($hotels->isEmpty()) {
            return response()->json(['message' => 'No hotels found for this user'], 404);
        }
    
        return response()->json($hotels);
    }
    
    public function index(Request $request)
    {
        // Check if the user_id is provided in the request
        if (!$request->has('user_id')) {
            return response()->json(['error' => 'User ID is required'], 400);
        }
    
        $userId = $request->query('user_id'); // Retrieve the user_id from the GET request
    
        // Fetch hotels saved by the user with the provided user_id
        $hotels = Hotel::where('user_id', $userId)->orderBy('created_at', 'desc')->get();
    
        // Return the hotels or an error message if none are found
        if ($hotels->isEmpty()) {
            return response()->json(['message' => 'No hotels found for this user'], 404);
        }
    
        return response()->json($hotels);
    }

    // Additional methods as needed...
}
