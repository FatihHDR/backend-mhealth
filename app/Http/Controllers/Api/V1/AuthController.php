<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

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

            return response()->json([
                'user' => $user,
                'token' => $plain,
                'expires_at' => $expiresAt,
            ]);
        } catch (\Throwable $e) {
            Log::error('Login error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'input' => $request->except('password'),
            ]);

            return response()->json(['message' => 'Login failed'], 500);
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

            return response()->json(['message' => 'Logged out']);
        } catch (\Throwable $e) {
            Log::error('Logout error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['message' => 'Logout failed'], 500);
        }
    }
}
