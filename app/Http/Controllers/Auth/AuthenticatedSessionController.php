<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Log;
use App\Models\User;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        if (config('app.debug')) {
            Log::debug('Auth store - before authenticate', [
                'session_id' => $request->session()->getId(),
                'cookies' => $request->headers->get('cookie'),
            ]);
        }

        $request->authenticate();

        if (config('app.debug')) {
            Log::debug('Auth store - after authenticate', [
                'session_id' => $request->session()->getId(),
                'cookies' => $request->headers->get('cookie'),
            ]);
        }

        // Get the authenticated user
        /** @var User|null $user */
        $user = Auth::user();

        // If the user has confirmed two-factor authentication, intercept login and send to 2FA challenge
        if ($user && $user->two_factor_confirmed_at !== null) {
            // Store user ID (and remember preference) in session for the 2FA challenge
            $request->session()->put('auth.2fa.id', $user->id);
            $request->session()->put('auth.2fa.remember', $request->boolean('remember'));

            // Log the user out so the session remains unauthenticated until 2FA completes
            Auth::guard('web')->logout();

            if (config('app.debug')) {
                Log::debug('Auth store - 2FA enabled, redirecting to challenge', [
                    'session_id' => $request->session()->getId(),
                    'user_id' => $user->id,
                ]);
            }

            return redirect()->route('2fa.challenge');
        }

        // 2FA not enabled for this user -> complete normal login
        $request->session()->regenerate();
        $request->session()->regenerateToken();

        if (config('app.debug')) {
            Log::debug('Auth store - after regenerate', [
                'session_id' => $request->session()->getId(),
                'cookies' => $request->headers->get('cookie'),
            ]);
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
