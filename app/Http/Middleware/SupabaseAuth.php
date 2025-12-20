<?php

namespace App\Http\Middleware;

use Closure;
use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\JWK;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http as HttpClient;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class SupabaseAuth
{
    /**
     * Handle an incoming request.
     * Verifies Supabase JWT using JWKS (RS256). Caches JWKS for performance.
     */
    public function handle(Request $request, Closure $next): Response
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

        // Build JWKS URL
        $supabaseUrl = config('supabase.url') ?: env('SUPABASE_URL');
        $jwksUrl = config('supabase.jwks_url') ?: env('SUPABASE_JWKS_URL');
        if (! $jwksUrl && $supabaseUrl) {
            $jwksUrl = rtrim($supabaseUrl, '/') . '/auth/v1/.well-known/jwks.json';
        }

        if (! $jwksUrl) {
            return response()->json(['message' => 'Server misconfigured: missing SUPABASE_JWKS_URL or SUPABASE_URL'], 500);
        }

        try {
            // Decode header to inspect alg/kid
            $header = $this->getHeaderFromToken($token);
            $alg = $header->alg ?? null;

            // If token signed with HS256 and secret is configured, verify with secret (legacy)
            if ($alg === 'HS256') {
                $secret = config('supabase.jwt_secret') ?: env('SUPABASE_JWT_SECRET');
                if (! $secret) {
                    throw new Exception('HS256 token but SUPABASE_JWT_SECRET is not configured');
                }

                $payload = JWT::decode($token, new Key($secret, 'HS256'));

            } else {
                // Load keys from cache or fetch JWKS
                $kid = $this->getKidFromToken($token);
            $cacheKey = 'supabase_jwks_keys_' . md5($jwksUrl);
            $keys = Cache::get($cacheKey);

            if (! is_array($keys) || empty($keys)) {
                $resp = HttpClient::get($jwksUrl);
                if (! $resp->ok()) {
                    throw new Exception('Failed to fetch JWKS');
                }

                $jwks = $resp->json();
                if (! is_array($jwks) || ! isset($jwks['keys'])) {
                    throw new Exception('Invalid JWKS response');
                }

                // Parse to kid => pem (JWK::parseKeySet returns array of kid=>key material)
                $parsed = JWK::parseKeySet($jwks);

                // Keep parsed keys in cache for 1 hour
                Cache::put($cacheKey, $parsed, 3600);
                $keys = $parsed;
            }

            if (! $kid || ! isset($keys[$kid])) {
                throw new Exception('Unable to find matching JWKS key');
            }

            $publicKey = $keys[$kid];

            // Verify token (RS256)
            $payload = JWT::decode($token, new Key($publicKey, 'RS256'));
            }
        } catch (Exception $e) {
            return response()->json(['message' => 'Invalid token', 'error' => $e->getMessage()], 401);
        }

        // Attach supabase info to request attributes
        $request->attributes->set('supabase_user', $payload);
        if (isset($payload->sub)) {
            $request->attributes->set('supabase_user_id', $payload->sub);
            $request->attributes->set('user_id', $payload->sub);
        }

        $role = $payload->user_role ?? ($payload->role ?? null);
        $request->attributes->set('supabase_user_role', $role ?? 'user');

        return $next($request);
    }

    private function base64UrlDecode(string $input): string
    {
        $remainder = strlen($input) % 4;
        if ($remainder) {
            $padlen = 4 - $remainder;
            $input .= str_repeat('=', $padlen);
        }
        return base64_decode(strtr($input, '-_', '+/'));
    }

    private function getHeaderFromToken(string $token): ?object
    {
        $parts = explode('.', $token);
        if (count($parts) < 2) return null;
        $headerB64 = $parts[0];
        $headerJson = $this->base64UrlDecode($headerB64);
        if (! $headerJson) return null;
        return json_decode($headerJson);
    }

    private function getKidFromToken(string $token): ?string
    {
        $header = $this->getHeaderFromToken($token);
        return $header->kid ?? null;
    }
}
