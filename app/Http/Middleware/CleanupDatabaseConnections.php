<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class CleanupDatabaseConnections
{
    /**
     * Handle an incoming request.
     * 
     * This middleware ensures database connections are properly cleaned up
     * after each request to prevent connection exhaustion with pgbouncer.
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            return $next($request);
        } finally {
            // Always disconnect after request to release connection back to pgbouncer pool
            DB::disconnect('pgsql');
        }
    }
}
