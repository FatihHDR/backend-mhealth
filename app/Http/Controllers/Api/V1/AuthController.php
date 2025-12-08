<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;

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
     * Send password reset link to the currently authenticated user's email.
     * The email will be sent from the configured noreply address (config/mail.from).
     */
    public function sendResetLinkAuthenticated(Request $request)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $email = $user->email;

        // Ensure mail 'from' is set to noreply; fall back to config/mail.from if available.
        $fromAddress = config('mail.from.address', 'noreply@localhost');
        $fromName = config('mail.from.name', 'No Reply');
        Mail::alwaysFrom($fromAddress, $fromName);

        $status = Password::sendResetLink(['email' => $email]);

        if ($status === Password::RESET_LINK_SENT) {
            Log::info('Password reset link sent', ['user_id' => $user->id, 'email' => $email, 'ip' => $request->ip()]);

            return response()->json(['message' => 'Tautan reset password telah dikirim ke email Anda.'], 200);
        }

        Log::warning('Failed to send password reset link', ['user_id' => $user->id, 'email' => $email, 'status' => $status]);

        return response()->json(['message' => 'Gagal mengirim tautan reset password. Silakan coba lagi.'], 500);
    }

    /**
     * Allow authenticated user to change their password (current + new).
     * Revokes the current access token after successful change.
     */
    public function changePassword(Request $request)
    {
        $data = $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        if (! Hash::check($data['current_password'], $user->password)) {
            return response()->json(['message' => 'Password saat ini tidak cocok.'], 422);
        }

        $user->password = Hash::make($data['password']);
        $user->save();

        // Revoke current token so user must re-auth (optional security step)
        if ($request->user()->currentAccessToken()) {
            $request->user()->currentAccessToken()->delete();
        }

        Log::info('User changed password', ['user_id' => $user->id, 'ip' => $request->ip()]);

        return response()->json(['message' => 'Password berhasil diperbarui. Silakan login ulang.']);
    }

    /**
     * Reset password using token (for frontend password reset flow).
     * Expects: token, email, password, password_confirmation
     */
    public function resetPassword(Request $request)
    {
        $data = $request->validate([
            'token' => 'required|string',
            'email' => 'required|email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $status = Password::reset(
            [
                'email' => $data['email'],
                'password' => $data['password'],
                'password_confirmation' => $data['password_confirmation'] ?? $data['password'],
                'token' => $data['token'],
            ],
            function ($user, $password) use ($request) {
                $user->password = Hash::make($password);
                $user->save();

                // Revoke all tokens (optional): remove all personal access tokens
                if (method_exists($user, 'tokens')) {
                    $user->tokens()->delete();
                }

                Log::info('Password reset via API', ['user_id' => $user->id, 'ip' => $request->ip()]);
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json(['message' => 'Password berhasil direset. Silakan login dengan password baru.']);
        }

        return response()->json(['message' => __($status)], 422);
    }

    /**
     * Login user with email and password
     */
    public function login(Request $request)
    {
        try {
            $raw = $request->getContent();
            $maskedPayload = null;
            if (! empty($raw)) {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    if (array_key_exists('password', $decoded)) {
                        $decoded['password'] = '***';
                    }
                    $maskedPayload = $decoded;
                }
            }

            Log::debug('Login request input (masked)', [
                'input' => $request->except('password'),
                'payload' => $maskedPayload,
            ]);

            $data = $request->validate([
                'email' => 'required|email',
                'password' => 'required|string',
            ]);

            if (! Auth::attempt(['email' => $data['email'], 'password' => $data['password']])) {
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

            // Create an HttpOnly cookie for the token using env-driven session settings.
            // Cookie lifetime: 7 days (in minutes).
            $minutes = 60 * 24 * 7;

            // Read cookie options from env/config so behavior can be controlled per environment
            $domain = env('SESSION_DOMAIN', null);
            $secure = (bool) env('SESSION_SECURE_COOKIE', app()->environment('production'));
            $sameSite = env('SESSION_SAME_SITE', 'none');

            // Build cookie with explicit SameSite value from config. Note: SameSite=None requires Secure=true.
            $cookie = cookie('api_token', $plain, $minutes, '/', $domain, $secure, true, false, $sameSite);

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

            if (! $email) {
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
    // public function redirectToProvider()
    // {
    //     return Socialite::driver('google')->redirect();
    // }

    // /**
    //  * Obtain the user information from Google and login/create local user (web callback)
    //  * Redirects to FRONTEND_URL with token as query param (optional).
    //  */
    // public function handleProviderCallback()
    // {
    //     try {
    //         $googleUser = Socialite::driver('google')->user();

    //         $email = $googleUser->getEmail();
    //         $name = $googleUser->getName() ?? $googleUser->getNickname();

    //         if (! $email) {
    //             return redirect(config('app.url'));
    //         }

    //         $user = User::updateOrCreate(
    //             ['email' => $email],
    //             [
    //                 'full_name' => $name ?? $email,
    //             ]
    //         );

    //         // Save provider data
    //         $user->google_id = $googleUser->getId();
    //         if (property_exists($googleUser, 'token')) {
    //             $user->google_token = $googleUser->token;
    //         }
    //         if (property_exists($googleUser, 'refreshToken')) {
    //             $user->google_refresh_token = $googleUser->refreshToken;
    //         }
    //         $user->save();

    //         Auth::login($user);

    //         // Create Sanctum token to pass to frontend (optional)
    //         $newToken = $user->createToken('api-token');
    //         $plain = $newToken->plainTextToken;

    //         $frontend = env('FRONTEND_URL');
    //         if ($frontend) {
    //             // Redirect with token in query string (frontend should read and store securely)
    //             return Redirect::away(rtrim($frontend, '/').'/?token='.$plain);
    //         }

    //         return redirect('/');
    //     } catch (\Throwable $e) {
    //         Log::error('Google callback error', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

    //         return redirect(config('app.url'));
    //     }
    // }
}
