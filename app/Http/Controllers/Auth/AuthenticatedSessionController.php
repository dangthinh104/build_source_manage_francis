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
            \Log::debug('Auth store - before authenticate', [
                'session_id' => $request->session()->getId(),
                'cookies' => $request->headers->get('cookie'),
            ]);
        }

        $request->authenticate();

        if (config('app.debug')) {
            \Log::debug('Auth store - after authenticate', [
                'session_id' => $request->session()->getId(),
                'cookies' => $request->headers->get('cookie'),
            ]);
        }

        // Get the authenticated user
        $user = Auth::user();

        // Check if 2FA is enabled for this user
        if ($user && $user->two_factor_enabled && $user->two_factor_secret !== null) {
            // Check if device is remembered (skip 2FA if verified within 1 day)
            $rememberedDevice = $request->cookie('device_2fa_remembered_' . $user->id);
            
            if (!$rememberedDevice || $rememberedDevice !== hash('sha256', $user->id . $user->email)) {
                // 2FA is required and device is not remembered
                // Log out the user temporarily
                Auth::logout();
                
                // Store user ID in session for 2FA verification
                $request->session()->put('auth.2fa.id', $user->id);
                $request->session()->put('auth.2fa.remember', $request->boolean('remember'));
                
                return redirect()->route('2fa.verify');
            }
        }

        // Either 2FA is not enabled or device is remembered - proceed with normal login
        $request->session()->regenerate();
        $request->session()->regenerateToken();

        if (config('app.debug')) {
            \Log::debug('Auth store - after regenerate', [
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
