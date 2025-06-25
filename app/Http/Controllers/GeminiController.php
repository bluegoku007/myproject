<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Activity;
class GeminiController extends Controller
{
    public function generateContent()
    {
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . env('GEMINI_API_KEY');

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post($url, [
            'contents' => [
                [
                    'parts' => [
                        ['text' => 'Explain how AI works in a few words']
                    ]
                ]
            ]
        ]);

        return response()->json([
            'status' => $response->status(),
            'data' => $response->json(),
        ]);
    }




public function getActivities(Request $request)
{
    $city = $request->input('city');
    $startDate = $request->input('start_date');
    $endDate = $request->input('end_date');
    $budget = $request->input('budget');
    $interests = $request->input('interests', []); // could be string or array

    // Convert interests to array if it's a string
    if (is_string($interests)) {
        $interests = array_map('trim', explode(',', $interests));
    }

    if (!$city || !$startDate || !$endDate || !$budget) {
        return response()->json(['error' => 'Missing parameters'], 400);
    }

    // Transformer le tableau en phrase "museums and beaches" ou "museums, beaches and parks"
    $interestsText = '';
    if (count($interests) > 1) {
        $lastInterest = array_pop($interests);
        $interestsText = implode(', ', $interests) . ' and ' . $lastInterest;
    } elseif (count($interests) == 1) {
        $interestsText = $interests[0];
    } else {
        $interestsText = 'museums'; // valeur par défaut si aucun intérêt fourni
    }

    $prompt = "I am going to $city from $startDate to $endDate and I have $budget USD. And I'm a fan of $interestsText. Give me some places I can visit day by day and also give me restaurants day by day like breakfast,lunch,dinner. You can use https://www.google.com/maps for data. and i want to number the days like day1 day2";

    $url = 'https://api.groq.com/openai/v1/chat/completions';

    $response = Http::withHeaders([
        'Authorization' => 'Bearer ' . env('GROQ_API_KEY'),
        'Content-Type' => 'application/json',
    ])->post($url, [
        'model' => 'compound-beta',
        'messages' => [
            [
                'role' => 'user',
                'content' => $prompt,
            ],
        ],
        'max_tokens' => 1000,
        'temperature' => 0.7,
    ]);

    if ($response->successful()) {
        $data = $response->json();
        $answer = $data['choices'][0]['message']['content'] ?? 'No response from AI';

        return response()->json([
            'prompt' => $prompt,
            'recommendations' => $answer,
        ]);
    }

    return response()->json([
        'error' => 'AI API request failed',
        'details' => $response->body(),
    ], $response->status());
}

public function store(Request $request)
{
    $validated = $request->validate([
        'city' => 'required|string',
        'start_date' => 'required|date',
        'end_date' => 'required|date',
        'budget' => 'required|numeric',
        'interests' => 'nullable|array',
        'prompt' => 'required|string',
        'recommendations' => 'required|string',
    ]);

    $user = auth()->user();

    $activity = new Activity();
    $activity->user_id = $user->id;
    $activity->city = $validated['city'];
    $activity->start_date = $validated['start_date'];
    $activity->end_date = $validated['end_date'];
    $activity->budget = $validated['budget'];
    $activity->interests = json_encode($validated['interests']); // JSON si tableau
    $activity->prompt = $validated['prompt'];
    $activity->recommendations = $validated['recommendations'];
    $activity->save();

    return response()->json([
        'message' => 'Activity saved successfully!',
        'activity' => $activity
    ], 201);
}

public function getActivitiesByUser(Request $request)
{
    $userId = $request->user()->id;

    $activities = Activity::where('user_id', $userId)->get();

    return response()->json($activities);


}
}