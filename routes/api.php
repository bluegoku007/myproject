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

// Apply CORS middleware to all routes
Route::middleware(CorsMiddleware::class)->group(function () {
    // Public routes (no authentication required)
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::get('/currencies', [CurrencyController::class, 'getCurrencies']);
    Route::get('/countries', [CountryCityController::class, 'getCountries']);
    Route::get('/cities', [CountryCityController::class, 'getCities']);
    Route::get('/reverse-geocode', [GeolocationController::class, 'getCountry']);
    Route::get('/flights', [FlightController::class, 'searchFlights']);

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