<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache; // Add this line
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Mail; // Add this line
use App\Mail\VerificationCodeMail; // Ensure this exists
use Illuminate\Support\Facades\Log; // Added missing Log facade

class AuthController extends Controller
{

 // 🔹 SEND VERIFICATION CODE
 public function sendVerification(Request $request)
 {
     $request->validate([
         'email' => 'required|email|unique:users,email',
         'name' => 'required|string|max:255'
     ]);
 
     try {
         $code = random_int(100000, 999999);
         $email = $request->email;
 
         Cache::put('verification_'.$email, $code, now()->addMinutes(15));
 
         // Add debug logging before sending
         Log::info('Attempting to send verification email to: '.$email);
         
         Mail::to($email)->send(new VerificationCodeMail($code));
         
         Log::info('Verification email sent successfully to: '.$email);
 
         return response()->json([
             'success' => true,
             'message' => 'Verification code sent successfully',
             'expires_in' => 15
         ]);
 
     } catch (\Exception $e) {
         Log::error('Verification error: '.$e->getMessage());
         Log::error('Stack trace: '.$e->getTraceAsString());
         
         return response()->json([
             'success' => false,
             'message' => 'Failed to send verification code',
             'error' => config('app.debug') ? $e->getMessage() : null
         ], 500);
     }
 }
    // 🔹 REGISTER (avec rôle par défaut)
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'verification_code' => 'required|string|min:6|max:6'
        ]);
    
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
    
        // Verify code
        $storedCode = Cache::get('verification_'.$request->email);
        
        if (!$storedCode || $storedCode != $request->verification_code) {
            return response()->json([
                'message' => 'Invalid or expired verification code'
            ], 422);
        }
    
        // Create user
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'user',
            'email_verified_at' => now() // Mark as verified
        ]);
    
        // Clear verification code
        Cache::forget('verification_'.$request->email);
    
        event(new Registered($user));
    
        // Create access token
        $token = $user->createToken('auth_token')->plainTextToken;
    
        return response()->json([
            'user' => $user,
            'token' => $token,
            'message' => 'Registration successful'
        ], 201);
    }

    // 🔹 LOGIN (Retourne aussi le rôle)
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $token = $user->createToken('YourAppName')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
            'email' => $user->email,
            'role' => $user->role, // 🔹 Ajout du rôle ici
        ]);
    }

    // 🔹 LOGOUT
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Successfully logged out']);
    }

    // 🔹 Récupérer les informations de l'utilisateur connecté
    public function user(Request $request)
    {
        return response()->json([
            'id' => $request->user()->id,
            'name' => $request->user()->name,
            'email' => $request->user()->email,
            'role' => $request->user()->role, // 🔹 Ajout du rôle ici
        ]);
    }

    // 🔹 Compter les utilisateurs
    public function count()
    {
        $userCount = User::count();
        return response()->json(['count' => $userCount]);
    }
}
