<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Mail;
use App\Mail\VerificationCodeMail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Password;
use App\Notifications\CustomResetPassword;
use App\Mail\PasswordResetMail;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Auth;


class AuthController extends Controller
{
    public function sendResetLink(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        
        try {
            $user = User::where('email', $request->email)->first();
            
            if (!$user) {
                return response()->json([
                    'success' => true, // Still return success for security
                    'message' => 'If this email exists, a reset link has been sent'
                ]);
            }
            
            $token = Str::random(60);
            \DB::table('password_resets')->updateOrInsert(
                ['email' => $request->email],
                ['token' => Hash::make($token), 'created_at' => now()]
            );
            
            // Send using Mailable instead of Notification
            Mail::to($user->email)->send(new PasswordResetMail($token, $user->email));
            
            Log::info("Password reset email sent to: {$user->email}");
            
            return response()->json([
                'success' => true,
                'message' => 'Reset link sent successfully'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Password reset error: '.$e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to send reset link',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
    


    public function redirectToGoogle()
    {
        return Socialite::driver('google')->stateless()->redirect();
    }
    
    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
            
            $user = User::where('email', $googleUser->getEmail())->first();
            
            if (!$user) {
                $user = User::create([
                    'name' => $googleUser->getName(),
                    'email' => $googleUser->getEmail(),
                    'password' => Hash::make(Str::random(24)),
                    'role' => 'user',
                    'email_verified_at' => now()
                ]);
            }
    
            $token = $user->createToken('auth_token')->plainTextToken;
    
            // Redirect to frontend with token in URL fragment
            return redirect(env('FRONTEND_URL').'/auth/callback#'.http_build_query([
                'token' => $token,
                'user' => json_encode($user->toArray()),
                'expires_in' => 3600
            ]));
    
        } catch (\Exception $e) {
            Log::error('Google login error: '.$e->getMessage());
            return redirect(env('FRONTEND_URL').'/login?error='.urlencode($e->getMessage()));
        }
    }



// In your AuthController



public function handleGoogleCallbackFlutter()
{
    try {
        $googleUser = Socialite::driver('google')->stateless()->user();

        $user = User::where('email', $googleUser->getEmail())->first();

        if (!$user) {
            $user = User::create([
                'name' => $googleUser->getName(),
                'email' => $googleUser->getEmail(),
                'password' => Hash::make(Str::random(24)),
                'role' => 'user',
                'email_verified_at' => now(),
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return redirect(env('FLUTTER_URL') . '/auth/callback#' . http_build_query([
            'token' => $token,
            'user' => json_encode($user->only(['id', 'name', 'email'])),
            'expires_in' => 3600,
        ]));
    } catch (\Exception $e) {
        \Log::error('Google login (Flutter) error: ' . $e->getMessage());

        return redirect(env('FLUTTER_URL') . '/login?error=' . urlencode($e->getMessage()));
    }
}



    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);
        
        try {
            // Manual token verification
            $record = \DB::table('password_resets')
                ->where('email', $request->email)
                ->first();
            
            if (!$record || !Hash::check($request->token, $record->token)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or expired token'
                ], 422);
            }
            
            // Update user password
            $user = User::where('email', $request->email)->first();
            $user->password = Hash::make($request->password);
            $user->save();
            
            // Delete token
            \DB::table('password_resets')->where('email', $request->email)->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Password reset successfully'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Password reset error: '.$e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Password reset failed'
            ], 500);
        }
    }

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



public function handleFlutterGoogleToken(Request $request)
{
    $request->validate([
        'access_token' => 'required|string',
    ]);

    try {
        $googleUser = Socialite::driver('google')->userFromToken($request->access_token);
        
        $user = User::updateOrCreate(
            ['email' => $googleUser->email],
            [
                'name' => $googleUser->name,
                'google_id' => $googleUser->id,
                'password' => Hash::make(Str::random(24)),
            ]
        );

        $token = $user->createToken('google-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user,
        ]);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
}

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
    
        $storedCode = Cache::get('verification_'.$request->email);
        
        if (!$storedCode || $storedCode != $request->verification_code) {
            return response()->json([
                'message' => 'Invalid or expired verification code'
            ], 422);
        }
    
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'user',
            'email_verified_at' => now()
        ]);
    
        Cache::forget('verification_'.$request->email);
    
        event(new Registered($user));
    
        $token = $user->createToken('auth_token')->plainTextToken;
    
        return response()->json([
            'user' => $user,
            'token' => $token,
            'message' => 'Registration successful'
        ], 201);
    }

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
            'role' => $user->role,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Successfully logged out']);
    }

    public function user(Request $request)
    {
        return response()->json([
            'id' => $request->user()->id,
            'name' => $request->user()->name,
            'email' => $request->user()->email,
            'role' => $request->user()->role,
        ]);
    }

    public function count()
    {
        $userCount = User::count();
        return response()->json(['count' => $userCount]);
    }
}