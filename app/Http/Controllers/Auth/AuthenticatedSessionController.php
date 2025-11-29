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

        // Validate credentials (may log the user in depending on implementation)
        $request->authenticate();

        if (config('app.debug')) {
            Log::debug('Auth store - after authenticate', [
                'session_id' => $request->session()->getId(),
                'cookies' => $request->headers->get('cookie'),
            ]);
        }

        // Get the authenticated user (may be logged in briefly by authenticate())
        /** @var User|null $user */
        $user = Auth::user();

        if (!$user) {
            // Unexpected: authentication did not produce a user
            return redirect()->route('login')->withErrors([
                'email' => 'Authentication failed. Please try again.',
            ]);
        }

        // CASE A: 2FA is enabled but not yet confirmed -> send to setup
        if ($user->shouldRedirectToTwoFactorSetup()) {
            // Store user id for setup flow and ensure no authenticated session remains
            $request->session()->put('auth.2fa.setup_id', $user->id);
            Auth::guard('web')->logout();

            if (config('app.debug')) {
                Log::debug('Auth store - redirecting to 2FA setup', [
                    'session_id' => $request->session()->getId(),
                    'user_id' => $user->id,
                ]);
            }

            return redirect()->route('2fa.setup');
        }

        // CASE B: 2FA is enabled and confirmed -> send to challenge
        if ($user->shouldRedirectToTwoFactorChallenge()) {
            // Store user id for challenge flow and ensure no authenticated session remains
            $request->session()->put('auth.2fa.id', $user->id);
            Auth::guard('web')->logout();

            if (config('app.debug')) {
                Log::debug('Auth store - redirecting to 2FA challenge', [
                    'session_id' => $request->session()->getId(),
                    'user_id' => $user->id,
                ]);
            }

            return redirect()->route('2fa.challenge');
        }

        // CASE C: No 2FA -> complete normal login
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
