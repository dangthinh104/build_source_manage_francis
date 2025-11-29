<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorController extends Controller
{
    /**
     * Display the two-factor authentication challenge view.
     */
    public function create(Request $request): Response|RedirectResponse
    {
        // Ensure there is a pending 2FA authentication
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
        // Validate code input
        $request->validate([
            'code' => ['required', 'string'],
        ]);

        // Retrieve pending user id
        $userId = $request->session()->get('auth.2fa.id');

        if (!$userId) {
            return redirect()->route('login');
        }

        $user = User::find($userId);

        if (!$user || !$user->two_factor_secret) {
            return redirect()->route('login');
        }

        $google2fa = new Google2FA();
        $secret = decrypt($user->two_factor_secret);

        $valid = $google2fa->verifyKey($secret, $request->input('code'));

        if (!$valid) {
            return back()->withErrors(['code' => 'The provided code was invalid.']);
        }

        // Valid - complete login
        Auth::login($user);

        // Clear 2FA session and regenerate
        $request->session()->forget('auth.2fa.id');
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
