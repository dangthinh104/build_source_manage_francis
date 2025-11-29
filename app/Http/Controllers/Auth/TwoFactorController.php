<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorController extends Controller
{
    // Flow 1: Setup (First Time)

    /**
     * Display the two-factor authentication setup view for first-time configuration.
     */
    public function setup(Request $request): Response|RedirectResponse
    {
        // Check session for setup flow
        if (!$request->session()->has('auth.2fa.setup_id')) {
            return redirect()->route('login');
        }

        $userId = $request->session()->get('auth.2fa.setup_id');
        $user = User::find($userId);

        if (!$user) {
            return redirect()->route('login');
        }

        // Initialize Google2FA
        $google2fa = new Google2FA();

        // Generate new secret if missing
        if (!$user->two_factor_secret) {
            $secret = $google2fa->generateSecretKey();
            $user->two_factor_secret = encrypt($secret);
            $user->save();
        } else {
            $secret = decrypt($user->two_factor_secret);
        }

        // Generate QR Code SVG
        $qrCodeUrl = $google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $secret
        );

        $renderer = new ImageRenderer(
            new RendererStyle(200),
            new SvgImageBackEnd()
        );
        $writer = new Writer($renderer);
        $qrCodeSvg = $writer->writeString($qrCodeUrl);

        return Inertia::render('Auth/TwoFactorSetup', [
            'qrCode' => $qrCodeSvg,
            'secret' => $secret,
        ]);
    }

    /**
     * Confirm the two-factor authentication setup.
     */
    public function confirm(Request $request): RedirectResponse
    {
        // Validate the input
        $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ], [
            'code.required' => 'Please enter the authentication code.',
            'code.size' => 'The authentication code must be 6 digits.',
        ]);

        // Get user from session
        $userId = $request->session()->get('auth.2fa.setup_id');

        if (!$userId) {
            return redirect()->route('login')->withErrors(['code' => 'Session expired. Please login again.']);
        }

        $user = User::find($userId);

        if (!$user || !$user->two_factor_secret) {
            return redirect()->route('login')->withErrors(['code' => 'Invalid session. Please login again.']);
        }

        // Verify the code
        $google2fa = new Google2FA();
        $secret = decrypt($user->two_factor_secret);

        $valid = $google2fa->verifyKey($secret, $request->input('code'));

        if (!$valid) {
            return back()->withErrors(['code' => 'Invalid authentication code. Please try again.']);
        }

        // Success - Set confirmed timestamp and generate recovery codes
        $user->two_factor_confirmed_at = now();
        $user->two_factor_recovery_codes = encrypt(json_encode($this->generateRecoveryCodes()));
        $user->save();

        // Complete login
        Auth::login($user);

        // Clear session and regenerate
        $request->session()->forget('auth.2fa.setup_id');
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('status', 'Two-factor authentication has been enabled successfully!');
    }

    // Flow 2: Challenge (Login)

    /**
     * Display the two-factor authentication challenge view.
     */
    public function challenge(Request $request): Response|RedirectResponse
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
    public function verify(Request $request): RedirectResponse
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

    /**
     * Generate recovery codes for 2FA backup.
     */
    private function generateRecoveryCodes(): array
    {
        $codes = [];
        for ($i = 0; $i < 8; $i++) {
            $codes[] = Str::random(10) . '-' . Str::random(10);
        }
        return $codes;
    }
}
