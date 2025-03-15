<?php

namespace App\Http\Controllers;

use App\Models\Trip;
use Illuminate\Http\Request;

class TripController extends Controller {
    public function store(Request $request) {
        $validated = $request->validate([
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

    public function index() {
        $trips = Trip::orderBy('created_at', 'desc')->get();
        return response()->json($trips);
    }
    
}
