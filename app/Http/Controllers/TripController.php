<?php

namespace App\Http\Controllers;

use App\Models\Trip;
use Illuminate\Http\Request;

class TripController extends Controller {
    
    public function store(Request $request) {
        $user = auth()->user(); // ✅ Récupérer l'utilisateur connecté via le token

        $validated = $request->validate([
            'email' => 'required|email', // ✅ Validation de l'email
            'origin' => 'required|string|max:255',
            'destination' => 'required|string|max:255',
            'fromDate' => 'required|date',
            'toDate' => 'required|date|after:fromDate',
            'adults' => 'required|integer|min:1',
            'budget' => 'required|numeric|min:0',
            'selectedFlight' => 'required|array',
            'interests' => 'nullable|array',
            'currency' => 'required|string|max:3',
        ]);

        $trip = Trip::create([
            'email' => $user->email, // ✅ Utiliser l'email de l'utilisateur connecté
            'origin' => $validated['origin'],
            'destination' => $validated['destination'],
            'from_date' => $validated['fromDate'],
            'to_date' => $validated['toDate'],
            'adults' => $validated['adults'],
            'budget' => $validated['budget'],
            'selected_flight' => json_encode($validated['selectedFlight']),
            'interests' => json_encode($validated['interests'] ?? []),
            'currency' => $validated['currency'],
        ]);

        return response()->json(['message' => 'Trip saved successfully!', 'trip' => $trip], 201);
    }
    
    public function index(Request $request) { // ✅ Ajouter Request $request
        // Vérifier si l'email est bien envoyé
        if (!$request->has('email')) {
            return response()->json(['error' => 'Email is required'], 400);
        }

        $email = $request->query('email'); // ✅ Récupérer l'email depuis la requête GET

        // ✅ Récupérer uniquement les voyages de l'utilisateur
        $trips = Trip::where('email', $email)->orderBy('created_at', 'desc')->get();

        return response()->json($trips);
    }


    public function popularDestinations() {
        $popularDestinations = Trip::select('destination')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('destination')
            ->orderByDesc('count')
            ->limit(7) // ✅ Prendre les 7 destinations les plus populaires
            ->get();
    
        return response()->json($popularDestinations);
    }
    
    public function popularorigins() {
        $popularorigins = Trip::select('origin')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('origin')
            ->orderByDesc('count')
            ->limit(7) // ✅ Prendre les 7 origins les plus populaires
            ->get();
    
        return response()->json($popularorigins);
    }


}
