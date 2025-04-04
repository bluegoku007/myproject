<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Firebase\JWT\JWT;
use Firebase\JWT\JWK;

class GoogleAuthController extends Controller
{
    public function googleLogin(Request $request)
    {
        $idToken = $request->input('id_token');

        // Récupérer les clés publiques de Google pour vérifier le JWT
        $keys = json_decode(file_get_contents('https://www.googleapis.com/oauth2/v3/certs'), true);

        try {
            $decoded = JWT::decode($idToken, JWK::parseKeySet($keys), ['RS256']);

            // Vérifier que le token appartient bien à Google
            if ($decoded->iss !== 'https://accounts.google.com') {
                return response()->json(['error' => 'Token invalide'], 401);
            }

            // Récupérer ou créer l'utilisateur
            $user = User::updateOrCreate([
                'email' => $decoded->email,
            ], [
                'name' => $decoded->name,
                'google_id' => $decoded->sub, // ID unique Google
                'password' => bcrypt(uniqid()),
            ]);

            // Générer un token Laravel Sanctum
            $token = $user->createToken('API Token')->plainTextToken;

            return response()->json([
                'user' => $user,
                'token' => $token,
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Échec de l\'authentification Google'], 401);
        }
    }
}
