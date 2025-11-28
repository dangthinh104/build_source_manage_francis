<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     * If a route requires admin access, it should pass 'role' => 'admin' or 'super_admin' in middleware parameters.
     */
    public function handle(Request $request, Closure $next, $required = null): Response
    {
        $user = Auth::user();
        if (!$user) {
            abort(403, 'Unauthorized.');
        }

        $role = strtolower($user->role ?? 'user');

        // If no specific role required, allow authenticated users
        if (!$required) {
            return $next($request);
        }

        $required = strtolower($required);

        // Super admin bypasses everything
        if ($role === 'super_admin') {
            return $next($request);
        }

        // Admin can access admin routes but not super_admin-only routes
        if ($required === 'admin' && $role === 'admin') {
            return $next($request);
        }

        abort(403, 'Unauthorized role.');
    }
}
