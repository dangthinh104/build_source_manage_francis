<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForcePasswordChange
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->must_change_password) {
            // Allow access to the password change route and logout route
            // Adjust the route names as per your implementation
            if ($request->routeIs('password.change') || $request->routeIs('password.update') || $request->routeIs('logout')) {
                return $next($request);
            }

            return redirect()->route('password.change');
        }

        return $next($request);
    }
}
