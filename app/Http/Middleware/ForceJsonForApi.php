<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ForceJsonForApi
{
    /**
     * Force JSON responses for API requests by setting Accept header.
     *
     * @param  Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // If request already expects JSON, do nothing.
        if (! $request->expectsJson()) {
            $request->headers->set('Accept', 'application/json');
        }

        // Also set X-Requested-With to help some libraries identify AJAX requests
        if (! $request->headers->has('X-Requested-With')) {
            $request->headers->set('X-Requested-With', 'XMLHttpRequest');
        }

        return $next($request);
    }
}
