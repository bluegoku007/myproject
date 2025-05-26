<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

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

public function getRestaurantsByDay(Request $request)
{
    $city = $request->input('city');
    $startDate = $request->input('start_date');
    $endDate = $request->input('end_date');
    $budget = $request->input('budget');

    if (!$city || !$startDate || !$endDate || !$budget) {
        return response()->json(['error' => 'Missing parameters'], 400);
    }

    $prompt = "I am going to $city from $startDate to $endDate and I have $budget USD. I want you to give me only the names of restaurants that I can go to during these days, day by day. I want breakfast, lunch, and dinner suggestions.for breakfast i want coffee shops name You can use https://www.google.com/maps for data.";

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
            'restaurants' => $answer,
        ]);
    }

    return response()->json([
        'error' => 'AI API request failed',
        'details' => $response->body(),
    ], $response->status());
}


public function getMuseumsByDay(Request $request)
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

    $prompt = "I am going to $city from $startDate to $endDate and I have $budget USD. And I'm a fan of $interestsText. Give me some places I can visit day by day. You can use https://www.google.com/maps for data. and i want to number the days like day1 day2";

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


}
