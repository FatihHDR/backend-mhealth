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

            $validKey = config('app.api_secret_key') ?: env('API_SECRET_KEY');

            if (! $validKey) {
                return response()->json([
                    'message' => 'API key authentication not configured'
                ], 500);
            }

            if (! $apiKey || $apiKey !== $validKey) {
                return response()->json([
                    'message' => 'Invalid or missing API key'
                ], 401);
            }

            return $next($request);
        }

        /**
         * ==================================
         * NON-GET → SUPABASE ACCESS TOKEN
         * ==================================
         */
        return app(SupabaseAuth::class)->handle($request,$next);
    }
}