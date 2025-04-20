<?php

namespace App\Http\Controllers;

use App\Models\Trip;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
    

    public function popularInterests()
    {
        $allInterests = Trip::all()
            ->flatMap(function ($trip) {
                // Convertir en tableau si ce n’est pas déjà un tableau
                $interests = is_array($trip->interests)
                    ? $trip->interests
                    : json_decode($trip->interests, true);
    
                return $interests ?? [];
            })
            ->filter()
            ->countBy()
            ->sortDesc()
            ->take(7)
            ->map(function ($count, $interest) {
                return [
                    'interest' => $interest,
                    'count' => $count,
                ];
            })
            ->values();
    
        return response()->json($allInterests);
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

    public function totalBudgetGlobal() {
        $totalBudget = Trip::sum('budget');
        return response()->json(['total_budget' => $totalBudget]);
    }
    public function averageBudget()
    {
        $average = Trip::avg('budget'); // Assuming 'budget' is your column name
        return response()->json(['average_budget' => $average]);
    }

    public function userWithMostBudget() {
        $user = Trip::select('email')
            ->selectRaw('SUM(budget) as total_budget')
            ->groupBy('email')
            ->orderByDesc('total_budget')
            ->first();
    
        if (!$user) {
            return response()->json(['message' => 'No trips found'], 404);
        }
    
        return response()->json([
            'email' => $user->email,
            'total_budget' => $user->total_budget,
        ]);
    }
    

}
