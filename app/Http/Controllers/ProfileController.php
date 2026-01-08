<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Profile\DeleteProfileRequest;
use App\Http\Requests\Profile\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;
use PragmaRX\Google2FA\Google2FA;
use PragmaRX\Google2FAQRCode\Google2FA as Google2FAQRCode;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * ProfileController
 *
 * Handles user profile management, including basic info update,
 * account deletion, and two-factor authentication setup.
 */
class ProfileController extends BaseController
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(DeleteProfileRequest $request): RedirectResponse
    {
        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

    /**
     * Begin enabling two-factor authentication: generate secret and QR code but do not save to DB yet.
     */
    public function enableTwoFactor(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        // Generate secret
        $google2fa = new Google2FA();
        $secret = $google2fa->generateSecretKey();

        // Store encrypted secret in session until user confirms
        $request->session()->put('2fa_setup_secret', encrypt($secret));

        // Build otpauth URL for authenticator apps
        $company = config('app.name', 'App');
        $label = $company . ':' . $user->email;
        $otpAuthUrl = $google2fa->getQRCodeUrl($company, $user->email, $secret);

        // Generate QR code SVG using google2fa-qrcode if available
        $qrSvg = null;
        try {
            $qr = new Google2FAQRCode();
            $qrSvg = $qr->getQRCodeInline($company, $user->email, $secret);
        } catch (\Throwable $e) {
            // If QR generation fails, continue and return the plain OTP URL instead
            $qrSvg = null;
        }

        return Inertia::render('Profile/TwoFactorSetup', [
            'qr_svg' => $qrSvg,
            'secret' => $secret,
            'otp_auth_url' => $otpAuthUrl,
        ]);
    }

    /**
     * Confirm and enable two-factor authentication using a code from the authenticator app.
     */
    public function confirmTwoFactor(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        $encryptedSecret = $request->session()->get('2fa_setup_secret');

        if (!$encryptedSecret) {
            return Redirect::route('profile.edit')->withErrors(['two_factor' => '2FA setup session expired. Please try again.']);
        }

        try {
            $secret = decrypt($encryptedSecret);
        } catch (\Throwable $e) {
            return Redirect::route('profile.edit')->withErrors(['two_factor' => 'Invalid 2FA setup data.']);
        }

        $google2fa = new Google2FA();
        $valid = $google2fa->verifyKey($secret, $request->input('code'));

        if (!$valid) {
            return Redirect::back()->withErrors(['code' => 'Invalid code']);
        }

        // Save the secret (encrypted) and mark confirmed
        $user = $request->user();
        $user->two_factor_secret = encrypt($secret);
        $user->two_factor_confirmed_at = Carbon::now();

        // Generate recovery codes (8 codes)
        $recovery = [];
        for ($i = 0; $i < 8; $i++) {
            $recovery[] = Str::random(10);
        }
        $user->two_factor_recovery_codes = encrypt(json_encode($recovery));

        $user->save();

        // Clear session setup secret
        $request->session()->forget('2fa_setup_secret');

        return Redirect::route('profile.edit')->with('status', 'Two-factor authentication enabled. Please store your recovery codes safely.');
    }

    /**
     * Disable two-factor authentication for the current user.
     */
    public function disableTwoFactor(Request $request): RedirectResponse
    {
        $user = $request->user();

        $user->two_factor_secret = null;
        $user->two_factor_confirmed_at = null;
        $user->two_factor_recovery_codes = null;

        $user->save();

        return Redirect::route('profile.edit')->with('status', 'Two-factor authentication disabled.');
    }
}
