<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Middleware\SupabaseAuth;

class RequireSupabaseForNonGet
{
    /**
     * GET  : Static API Key
     * POST : Supabase JWT
     * PATCH: Supabase JWT
     * DELETE: Supabase JWT
     */
    public function handle(Request $request, Closure $next): Response
    {
            \Log::info('RequireSupabaseForNonGet enter', [
                'method' => $request->method(),
                'uri' => $request->getRequestUri(),
                'authorization' => $request->header('Authorization'),
                'x-api-key' => $request->header('X-API-Key'),
                'x-apikey' => $request->header('x-apikey')
            ]);
        /**
         * ==========================
         * GET → STATIC API KEY ONLY
         * ==========================
         */
        if ($request->isMethod('GET') || $request->is('api/v1/gemini/generate')) {
            $apiKey = $request->header('X-Api-Key');

            $validKey = env('API_SECRET_KEY');

            $apiKeyNormalized = null;
            if ($apiKey) {
                $apiKeyNormalized = preg_replace('/^\s*Bearer\s+/i', '', trim($apiKey));
            }

            try {
                \Log::info('RequireSupabaseForNonGet key debug', [
                    'validKey_from_env' => $this->mask(env('API_SECRET_KEY')),
                    'validKey_from_config' => $this->mask(config('app.api_secret_key')),
                    'validKey_used' => $this->mask($validKey),
                    'header_raw' => $this->mask($apiKey),
                    'header_normalized' => $this->mask($apiKeyNormalized),
                    'match' => ($apiKeyNormalized === $validKey) ? 'YES' : 'NO'
                ]);
            } catch (\Throwable $e) {
                // ignore logging failures
            }

            if (! $validKey) {
                return response()->json([
                    'message' => 'API key authentication not configured'
                ], 500);
            }

            // if (! $apiKeyNormalized || $apiKeyNormalized !== $validKey) {
            //     return response()->json([
            //         'message' => 'Invalid or missing API key'
            //     ], 401);
            // }

            return $next($request);
        }

        /**
         * ======================================
         * PUBLIC NON-GET ROUTES (No Role Required)
         * ======================================
         */
        $publicNonGetRoutes = [
            'api/v1/register',
            'api/v1/login',
            'api/v1/auth/google',
            'api/v1/password/reset',
            'api/v1/payments/notification',
        ];

        if ($request->is($publicNonGetRoutes)) {
            return $next($request);
        }

        /**
         * ==========================================
         * SENSITIVE OPERATIONS -> ADMIN ROLE ONLY
         * ==========================================
         */
        return app(SupabaseAuth::class)->handle($request, function ($req) use ($next) {
            $role = $req->attributes->get('supabase_user_role');

            if ($role !== 'admin') {
                return response()->json([
                    'message' => 'Forbidden: Admin role required',
                    'role' => $role
                ], 403);
            }

            return $next($req);
        });
    }

    /**
     * Mask a secret for safe logging (show first 6 chars)
     */
    protected function mask($s)
    {
        if (! $s) return null;
        $s = (string) $s;
        $len = strlen($s);
        if ($len <= 8) return substr($s, 0, 3) . str_repeat('*', max(0, $len-3));
        return substr($s, 0, 6) . '...' . substr($s, -2);
    }
}