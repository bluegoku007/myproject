<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\CountryCityController;
use App\Http\Controllers\GeolocationController;
use App\Http\Controllers\FlightController;
use App\Http\Controllers\CurrencyController;
use App\Http\Controllers\TripController;
use App\Http\Middleware\CorsMiddleware;
use App\Http\Controllers\AirportController;
use App\Http\Controllers\IataController;
use App\Http\Controllers\RestaurantController;
use App\Http\Controllers\MuseumController;
use App\Http\Controllers\HotelController;
use Illuminate\Support\Facades\Mail;
use App\Models\User;
use App\Notifications\CustomResetPassword;
use App\Mail\VerificationCodeMail;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\Checkout\Session as StripeSession;
use App\Http\Controllers\GeminiController;

// --------------------
// 🔓 Routes publiques
// --------------------
Route::get('/reset-password/{token}', function ($token, Request $request) {
    return response()->json([
        'token' => $token,
        'email' => $request->email
    ]);
})->name('password.reset');

Route::get('/test-email', function () {
    try {
        $user = User::first();
        $user->notify(new CustomResetPassword('test-token'));
        Mail::to('selmenselmi5@gmail.com')->send(new VerificationCodeMail(123456));
        return 'Both emails sent successfully';
    } catch (\Exception $e) {
        return 'Error: ' . $e->getMessage();
    }
});

Route::middleware(CorsMiddleware::class)->group(function () {


    // Auth & Vérification
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('/send-verification', [AuthController::class, 'sendVerification']);
    Route::post('/forgot-password', [AuthController::class, 'sendResetLink']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);

    // Google OAuth
    Route::post('/auth/google/token', function (Request $request) {
        $token = $request->id_token;
        $client = new \Google_Client(['client_id' => env('GOOGLE_CLIENT_ID')]);
        $payload = $client->verifyIdToken($token);

        if ($payload) {
            $email = $payload['email'];
            $name = $payload['name'];

            $user = User::updateOrCreate(
                ['google_id' => $googleId],
                ['email' => $email, 'name' => $name, 'avatar' => $avatar]
            );

            $token = $user->createToken('google')->plainTextToken;

            return response()->json([
                'user' => $user,
                'token' => $token,
            ]);
        } else {
            return response()->json(['error' => 'Invalid ID token'], 401);
        }
    });

    Route::get('/auth/google', [AuthController::class, 'redirectToGoogle']);
    Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback']);
    Route::get('/auth/flutter/google/callback', [AuthController::class, 'handleGoogleCallbackFlutter']);
    // Route
    Route::post('/auth/flutter/google', [AuthController::class, 'handleFlutterGoogleToken']);

    // Stripe
    Route::post('/create-payment-intent', function (Request $request) {
        Stripe::setApiKey(env('STRIPE_SECRET'));

        $paymentIntent = PaymentIntent::create([
            'amount' => $request->input('amount'),
            'currency' => $request->input('currency', 'usd'),
            'automatic_payment_methods' => ['enabled' => true],
        ]);

        return response()->json([
            'clientSecret' => $paymentIntent->client_secret,
        ]);
    });

    Route::post('/create-checkout-session', function (Request $request) {
        Stripe::setApiKey(env('STRIPE_SECRET'));

        $session = StripeSession::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => $request->input('currency', 'usd'),
                    'product_data' => [
                        'name' => $request->input('name', 'Hotel Booking'),
                    ],
                    'unit_amount' => $request->input('amount'),
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => env('APP_URL') . '/success',
            'cancel_url' => env('APP_URL') . '/cancel',
        ]);

        return response()->json(['sessionId' => $session->id]);
    });

    // API publiques
    Route::get('/currencies', [CurrencyController::class, 'getCurrencies']);
    Route::get('/countries', [CountryCityController::class, 'getCountries']);
    Route::get('/cities', [CountryCityController::class, 'getCities']);
    Route::get('/reverse-geocode', [GeolocationController::class, 'getCountry']);
    Route::get('/flights', [FlightController::class, 'searchFlights']);
    Route::get('/airport-code', [AirportController::class, 'getAirportCodes']);
    Route::get('/iata', [IataController::class, 'getIataCode']);
    Route::get('/capital-iata', [IataController::class, 'getCapitalIata']);
    Route::get('/restaurants', [RestaurantController::class, 'getRestaurants']);
    Route::get('/museums', [MuseumController::class, 'index']);
    Route::get('/hotels', [HotelController::class, 'getHotels']);
    Route::get('/search-destination', [HotelController::class, 'searchDestination']);
    Route::get('/country', [GeolocationController::class, 'getCountry']);
    Route::get('/capital', [GeolocationController::class, 'getCapitalFromCoordinates']);

    // Stats et popularité
    Route::get('/trips/popular-destinations', [TripController::class, 'popularDestinations']);
    Route::get('/trips/popular-origins', [TripController::class, 'popularorigins']);
    Route::get('/trips/popular-interests', [TripController::class, 'popularInterests']);
    Route::get('/trips/total-budget', [TripController::class, 'totalBudgetGlobal']);
    Route::get('/trips/average', [TripController::class, 'averageBudget']);
    Route::get('/trips/user-with-most-budget', [TripController::class, 'userWithMostBudget']);
    Route::get('/trips/by-user', [TripController::class, 'index']);
    Route::get('/hotels/by-user', [HotelController::class, 'index']);
    Route::get('/trips', [TripController::class, 'index']);
    Route::put('/users/{id}', [UserController::class, 'update']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);
    // Users
    Route::get('/users', [UserController::class, 'index']);
    Route::post('/users', [UserController::class, 'store']);
    Route::get('/users/count', [UserController::class, 'count']);
    Route::get('/users/{id}', [UserController::class, 'show']);
});

// --------------------
// 🔐 Routes protégées
// --------------------

Route::get('/login', function () {
    return response()->json(['error' => 'unauthenticated'], 401);
})->name('login');


Route::middleware('auth:sanctum')->get('/verify-token', function() {
    return response()->json(['valid' => true]);
});


Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);


    // Enregistrement
    Route::post('/save-trip', [TripController::class, 'store']);
    Route::post('/hotels', [HotelController::class, 'store']);

    Route::post('/activities/prompt', [GeminiController::class, 'getActivities']);
    Route::post('/activities', [GeminiController::class, 'store']);
    Route::get('/activities/by-user', [GeminiController::class, 'getActivitiesByUser']);

});
