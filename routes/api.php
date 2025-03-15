<?php 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Http\Controllers\CsrfCookieController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Middleware\CorsMiddleware;
use App\Http\Controllers\CountryCityController;
use App\Http\Controllers\GeolocationController;
use App\Http\Controllers\FlightController;
use App\Http\Controllers\CurrencyController;
use App\Http\Controllers\TripController;

Route::middleware(CorsMiddleware::class)->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
});
Route::middleware('auth:sanctum')->post('logout', [AuthController::class, 'logout']);

Route::get('/users', [UserController::class, 'index']);
Route::post('/users', [UserController::class, 'store']);
Route::get('/users/count', [UserController::class, 'count']);

Route::get('/users/{id}', [UserController::class, 'show']);

// Route pour récupérer le nombre d'utilisateurs
Route::get('/currencies', [CurrencyController::class, 'getCurrencies']);
Route::get('/countries', [CountryCityController::class, 'getCountries']);
Route::get('/cities', [CountryCityController::class, 'getCities']);
Route::get('/reverse-geocode', [GeolocationController::class, 'getcountry']);
Route::post('/register', [AuthController::class, 'register']);
Route::get('/flights', [FlightController::class, 'searchFlights']);
Route::post('/save-trip', [TripController::class, 'store']);
Route::get('/trips', [TripController::class, 'index']);
