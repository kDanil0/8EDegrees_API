<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  ...$roles
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ...$roles)
    {
        if (!$request->user()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }
        
        // Get the user's role
        $userRole = $request->user()->role->roleName;
        
        // Check if the user has the required role
        if (!in_array($userRole, $roles)) {
            return response()->json(['message' => 'Unauthorized. Required role: ' . implode(', ', $roles)], 403);
        }
        
        return $next($request);
    }
} 