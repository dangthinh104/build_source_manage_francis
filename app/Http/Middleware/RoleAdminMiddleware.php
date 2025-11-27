<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
class RoleAdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        // Example: handling multiple roles (Admin and Default)
        if ($user) {
            if ( $user->role !== 'Admin') {
                abort(403, 'Unauthorized action.');
            }
        }
        return $next($request);
    }
}
