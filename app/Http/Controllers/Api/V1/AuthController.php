<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cookie;

class AuthController extends Controller
{
    /**
     * Register a new user (fullname, email, password)
     */
    public function register(Request $request)
    {
        try {
            // Normalize incoming name fields: accept 'fullname', 'full_name', or 'name'
            $normalizedName = $request->input('fullname') ?? $request->input('full_name') ?? $request->input('name');
            if ($normalizedName !== null) {
                $request->merge(['fullname' => $normalizedName]);
            }

            $data = $request->validate([
                'fullname' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:8',
            ]);

            $user = User::create([
                'full_name' => $data['fullname'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
            ]);

            $newToken = $user->createToken('api-token');
            $plain = $newToken->plainTextToken;
            $expiresAt = $newToken->accessToken->expires_at ?? null;

            Log::info('User registered', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'user' => $user,
                'token' => $plain,
                'expires_at' => $expiresAt,
            ], 201);
        } catch (\Throwable $e) {
            Log::error('Registration failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'input' => $request->except('password'),
            ]);

            return response()->json(['message' => 'Registration failed'], 500);
        }
    }


    /**
     * Login user with email and password
     */
    public function login(Request $request)
    {
        try {
            $data = $request->validate([
                'email' => 'required|email',
                'password' => 'required|string',
            ]);

            if (!Auth::attempt(['email' => $data['email'], 'password' => $data['password']])) {
                Log::warning('Login failed: invalid credentials', [
                    'email' => $data['email'],
                    'ip' => $request->ip(),
                ]);

                return response()->json(['message' => 'Invalid credentials'], 401);
            }

            $user = Auth::user();
            $newToken = $user->createToken('api-token');
            $plain = $newToken->plainTextToken;
            $expiresAt = $newToken->accessToken->expires_at ?? null;

            Log::info('User logged in', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip' => $request->ip(),
            ]);

            // Create an HttpOnly, Secure cookie for the token so client JS cannot read it.
            // Cookie lifetime: 7 days (in minutes). Secure flag enabled only in production.
            $minutes = 60 * 24 * 7;
            $secure = app()->environment('production');
            $cookie = cookie('api_token', $plain, $minutes, '/', null, $secure, true, false, 'Lax');

            // Return user and expiry but do not include the raw token in the JSON body.
            return response()->json([
                'user' => $user,
                'token' => $plain,
                'expires_at' => $expiresAt,
            ])->cookie($cookie);
        } catch (ValidationException $e) {
            // Validation errors are client errors; return 422 with details.
            Log::warning('Login validation failed', [
                'errors' => $e->errors(),
                'input' => $request->except('password'),
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'message' => 'Validasi gagal. Periksa input dan coba lagi.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            $errorId = (string) Str::uuid();

            Log::error('Login error', [
                'error_id' => $errorId,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'input' => $request->except('password'),
            ]);

            return response()->json([
                'message' => 'Login gagal — terjadi kesalahan pada server. Simpan "error_id" dan hubungi dukungan jika perlu.',
                'error_id' => $errorId,
            ], 500);
        }
    }

    /**
     * Logout (revoke current token)
     */
    public function logout(Request $request)
    {
        try {
            $user = $request->user();

            if ($user && $request->user()->currentAccessToken()) {
                $request->user()->currentAccessToken()->delete();
            }

            Log::info('User logged out', [
                'user_id' => $user?->id,
                'email' => $user?->email,
                'ip' => $request->ip(),
            ]);

            // Queue cookie removal so the Set-Cookie header clears the token on the client
            Cookie::queue(Cookie::forget('api_token'));

            return response()->json(['message' => 'Logged out'])->withCookie(Cookie::forget('api_token'));
        } catch (\Throwable $e) {
            Log::error('Logout error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Attempt to clear cookie even on error
            Cookie::queue(Cookie::forget('api_token'));

            return response()->json(['message' => 'Logout failed'], 500)->withCookie(Cookie::forget('api_token'));
        }
    }

    /**
     * Sign in / register using Google token from client (stateless)
     * Accepts `token` (id_token or access_token) in request body.
     */
    public function googleSignIn(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
        ]);

        try {
            $token = $request->input('token');

            // Fetch user info from Google using provided token
            $socialUser = Socialite::driver('google')->stateless()->userFromToken($token);

            $email = $socialUser->getEmail();
            $name = $socialUser->getName() ?? $socialUser->getNickname();

            if (!$email) {
                return response()->json(['message' => 'Google account has no email'], 422);
            }

            // Find or create user
            $user = User::firstOrCreate(
                ['email' => $email],
                ['full_name' => $name ?? $email, 'password' => Hash::make(str()->random(32))]
            );

            // Create Sanctum token
            $newToken = $user->createToken('api-token');
            $plain = $newToken->plainTextToken;
            $expiresAt = $newToken->accessToken->expires_at ?? null;

            Log::info('User signed in with Google', ['user_id' => $user->id, 'email' => $user->email]);

            return response()->json(['user' => $user, 'token' => $plain, 'expires_at' => $expiresAt]);
        } catch (\Throwable $e) {
            Log::error('Google sign-in error', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['message' => 'Google sign-in failed'], 500);
        }
    }

    /**
     * Redirect the user to Google's OAuth page (web flow)
     */
    public function redirectToProvider()
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Obtain the user information from Google and login/create local user (web callback)
     * Redirects to FRONTEND_URL with token as query param (optional).
     */
    public function handleProviderCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();

            $email = $googleUser->getEmail();
            $name = $googleUser->getName() ?? $googleUser->getNickname();

            if (!$email) {
                return redirect(config('app.url'));
            }

            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'full_name' => $name ?? $email,
                ]
            );

            // Save provider data
            $user->google_id = $googleUser->getId();
            if (property_exists($googleUser, 'token')) {
                $user->google_token = $googleUser->token;
            }
            if (property_exists($googleUser, 'refreshToken')) {
                $user->google_refresh_token = $googleUser->refreshToken;
            }
            $user->save();

            // Login the user
            Auth::login($user);

            // Create Sanctum token to pass to frontend (optional)
            $newToken = $user->createToken('api-token');
            $plain = $newToken->plainTextToken;

            $frontend = env('FRONTEND_URL');
            if ($frontend) {
                // Redirect with token in query string (frontend should read and store securely)
                return Redirect::away(rtrim($frontend, '/') . '/?token=' . $plain);
            }

            return redirect('/');
        } catch (\Throwable $e) {
            Log::error('Google callback error', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return redirect(config('app.url'));
        }
    }
}
