<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Inertia\Inertia;
use Inertia\Response;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorController extends Controller
{
    /**
     * Display the two-factor authentication challenge view.
     */
    public function index(Request $request): Response|RedirectResponse
    {
        // Check if user has a pending 2FA session
        if (!$request->session()->has('auth.2fa.id')) {
            return redirect()->route('login');
        }

        return Inertia::render('Auth/TwoFactorChallenge', [
            'status' => session('status'),
        ]);
    }

    /**
     * Verify the two-factor authentication code.
     */
    public function store(Request $request): RedirectResponse
    {
        // Validate the 2FA code
        $request->validate([
            'code' => ['required', 'string', 'size:6'],
            'remember_device' => ['boolean'],
        ]);

        // Get the user ID from session
        $userId = $request->session()->get('auth.2fa.id');
        
        if (!$userId) {
            return redirect()->route('login')->withErrors([
                'code' => 'Your session has expired. Please login again.',
            ]);
        }

        // Find the user
        $user = User::find($userId);

        if (!$user || !$user->two_factor_enabled || !$user->two_factor_secret) {
            return redirect()->route('login')->withErrors([
                'code' => 'Invalid authentication state.',
            ]);
        }

        // Verify the 2FA code
        $google2fa = new Google2FA();
        $secret = decrypt($user->two_factor_secret);
        
        $valid = $google2fa->verifyKey($secret, $request->input('code'));

        if (!$valid) {
            return back()->withErrors([
                'code' => 'The authentication code is invalid.',
            ]);
        }

        // Code is valid - log the user in
        $remember = $request->session()->get('auth.2fa.remember', false);
        Auth::loginUsingId($userId, $remember);

        // Clear the 2FA session data
        $request->session()->forget('auth.2fa.id');
        $request->session()->forget('auth.2fa.remember');

        // Regenerate session
        $request->session()->regenerate();

        // Set remember device cookie if requested (valid for 24 hours)
        $response = redirect()->intended(route('dashboard', absolute: false));

        if ($request->boolean('remember_device')) {
            $cookieValue = hash('sha256', $user->id . $user->email);
            $cookie = Cookie::make(
                'device_2fa_remembered_' . $user->id,
                $cookieValue,
                60 * 24, // 24 hours in minutes
                '/',
                null,
                true, // secure
                true, // httpOnly
                false,
                'lax'
            );
            
            $response->withCookie($cookie);
        }

        return $response;
    }
}
