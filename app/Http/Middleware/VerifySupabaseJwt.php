<?php

namespace App\Http\Middleware;

use Closure;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Exception;
use Illuminate\Http\Request;

class VerifySupabaseJwt
{
    /**
     * Verify Supabase JWT (HS256) using SUPABASE_JWT_SECRET env var.
     * If valid, injects `supabase_user` and `user_id` into the request attributes.
     */
    public function handle(Request $request, Closure $next)
    {
        $header = $request->header('Authorization');
        $token = null;

        if ($header) {
            if (stripos($header, 'bearer ') === 0) {
                $token = trim(substr($header, 7));
            } else {
                $token = trim($header);
            }
        } else {
            $token = $request->bearerToken();
        }

        if (! $token) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $secret = env('SUPABASE_JWT_SECRET');
        if (! $secret) {
            return response()->json(['message' => 'Server misconfigured: missing SUPABASE_JWT_SECRET'], 500);
        }

        try {
            $payload = JWT::decode($token, new Key($secret, 'HS256'));
        } catch (Exception $e) {
            return response()->json(['message' => 'Invalid token', 'error' => $e->getMessage()], 401);
        }

        $request->attributes->set('supabase_user', $payload);
        if (isset($payload->sub)) {
            $request->attributes->set('user_id', $payload->sub);
        }

        return $next($request);
    }
}
