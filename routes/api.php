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
// Apply CORS middleware to all routes

Route::middleware(CorsMiddleware::class)->group(function () {
    // Public routes (no authentication required)
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('/google-login', [AuthController::class, 'googleLogin']); // ✅ Ajouter ici
    Route::post('/send-verification', [AuthController::class, 'sendVerification']);
    
    Route::get('/trips/popular-destinations', [TripController::class, 'popularDestinations']);
    Route::get('/trips/popular-origins', [TripController::class, 'popularorigins']);

    Route::get('/trips/popular-interests', [TripController::class, 'popularInterests']);


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

    Route::get('/trips/total-budget', [TripController::class, 'totalBudgetGlobal']);
    Route::get('/trips/average', [TripController::class, 'averageBudget']);
    Route::get('/trips/user-with-most-budget', [TripController::class, 'userWithMostBudget']);

    // routes/api.php
    Route::get('/country', [GeolocationController::class, 'getCountry']);
    Route::get('/capital', [GeolocationController::class, 'getCapitalFromCoordinates']);

});

// Protected routes (authentication required)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/save-trip', [TripController::class, 'store']);
    Route::get('/trips', [TripController::class, 'index']);

});

// User-related routes (public or protected as needed)
Route::middleware(CorsMiddleware::class)->group(function () {
    Route::get('/users', [UserController::class, 'index']);
    Route::post('/users', [UserController::class, 'store']);
    Route::get('/users/count', [UserController::class, 'count']);
    Route::get('/users/{id}', [UserController::class, 'show']);
});