<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    /**
     * Handle an incoming request and require a specific role.
     * Usage in routes: ->middleware('role:admin')
     */
    public function handle(Request $request, Closure $next, string $role)
    {
        $userRole = $request->attributes->get('supabase_user_role', 'user');

        if ($userRole !== $role) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return $next($request);
    }
}
