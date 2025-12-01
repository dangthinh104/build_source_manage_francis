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

        // Always regenerate secret for fresh setup to avoid any corruption issues
        // This ensures the QR code will always work
        $secret = $google2fa->generateSecretKey();
        $user->two_factor_secret = encrypt($secret);
        $user->two_factor_confirmed_at = null; // Reset confirmation
        $user->two_factor_recovery_codes = null; // Clear old recovery codes
        $user->save();

        // Generate QR Code SVG with proper formatting
        $companyName = config('app.name', 'Laravel');
        $holderName = $user->email;
        
        $qrCodeUrl = $google2fa->getQRCodeUrl(
            $companyName,
            $holderName,
            $secret
        );

        $renderer = new ImageRenderer(
            new RendererStyle(200, 0), // 200px size, 0 margin
            new SvgImageBackEnd()
        );
        $writer = new Writer($renderer);
        $qrCodeSvg = $writer->writeString($qrCodeUrl);
        
        // Ensure SVG has proper dimensions and styling
        $qrCodeSvg = preg_replace(
            '/<svg/',
            '<svg width="200" height="200" style="max-width: 100%; height: auto;"',
            $qrCodeSvg,
            1
        );

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
        
        try {
            $secret = decrypt($user->two_factor_secret);
        } catch (\Exception $e) {
            \Log::error('2FA Setup - Failed to decrypt secret', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            return back()->withErrors(['code' => 'Invalid session. Please refresh and try again.']);
        }
        
        // Sanitize input: remove spaces and any non-numeric characters
        $code = preg_replace('/\s+/', '', $request->input('code'));
        $code = preg_replace('/\D/', '', $code);

        // Verify with window tolerance of 2 (allows +/- 1 minute time drift)
        // Increased from 1 to handle clock sync issues better
        $valid = $google2fa->verifyKey($secret, $code, 2);

        if (!$valid) {
            \Log::warning('2FA Setup Failed', [
                'user_id' => $user->id,
                'code_length' => strlen($code),
                'secret_length' => strlen($secret),
                'timestamp' => now()->timestamp,
            ]);
            return back()->withErrors(['code' => 'The authentication code is invalid. Please try again or rescan the QR code.']);
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
        
        try {
            $secret = decrypt($user->two_factor_secret);
        } catch (\Exception $e) {
            \Log::error('2FA Login - Failed to decrypt secret', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            return redirect()->route('login')->withErrors(['email' => '2FA configuration error. Please contact support.']);
        }
        
        // Sanitize input: remove spaces and any non-numeric characters
        $code = preg_replace('/\s+/', '', $request->input('code'));
        $code = preg_replace('/\D/', '', $code);

        // Verify with window tolerance of 2 (allows +/- 1 minute time drift)
        $valid = $google2fa->verifyKey($secret, $code, 2);

        if (!$valid) {
            return back()->withErrors(['code' => 'The authentication code is invalid. Please try again.']);
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
