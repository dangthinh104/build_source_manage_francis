# GITHUB COPILOT INSTRUCTIONS: FIX 2FA UI BREAKAGE & VALIDATION ERROR

## 1. Context & Issues
We are experiencing two critical issues in the 2FA Setup flow (Vue 3 + Laravel Inertia):
1.  **UI Layout Breakage:** The SVG QR Code is rendering without constraints, overflowing the container and breaking the page layout. The "Setup Key" text is unreadable.
2.  **Validation Error (500/422):** When scanning the QR and entering the code, the server throws a `ValidationException`: *"The provided two factor authentication code was invalid."*

## 2. Tasks Checklist

### Task A: UI Refactor (`TwoFactorSetup.vue`)
**Goal:** Create a polished, centered, and responsive UI using Tailwind CSS.
**Specific Fixes Required:**
1.  **QR Code Container:** Wrap the SVG (QR code) in a container with strict width/height.
    * *Example:* `<div class="p-4 bg-white inline-block rounded-lg" v-html="qrCode"></div>`
    * Force the inner SVG to scale: Use CSS deep selector or class replacement to ensure the SVG has `w-48 h-48` (approx 200px) and `max-w-full`.
2.  **Layout Structure:**
    * Use a `flex flex-col items-center` layout to center everything.
    * Add distinct spacing (`gap-6`) between the Title, Description, QR Code, Manual Key, and Input Form.
3.  **Manual Key Section:**
    * Display the text key in a `bg-gray-100` rounded box with `font-mono text-sm` for readability.
    * Add a **Copy to Clipboard** button icon next to the key.
4.  **Input Field:**
    * Use a centered input field with `tracking-[0.5em]` (wide letter spacing) since users are entering 6 digits.
    * Input type should be `text` (numeric pattern) with `inputmode="numeric"`.

### Task B: Deep Logic Check (`TwoFactorController.php`)
**Goal:** Fix the validation failure "Invalid Code".
**Action:** Review the `confirm` method logic.
1.  **Window Verification:** The default window might be too strict (0). Increase the window to `1` (allows +/- 30 seconds drift).
    * *Code change:* `$valid = $google2fa->verifyKey($user->two_factor_secret, $code, 1);`
2.  **Input Sanitization:** Ensure the code from the request is sanitized.
    * Remove spaces: `$code = preg_replace('/\s+/', '', $request->code);`
3.  **Secret Handling:**
    * Ensure `$user->two_factor_secret` is being decrypted if your User model uses `$casts = ['two_factor_secret' => 'encrypted']`. If it's plain text, ensure no extra whitespace was saved.
4.  **Time Sync Check:**
    * *Debugging Hint:* Add a temporary log in the controller `Log::info('Server Time: ' . now()->toIso8601String());` to compare with the user's authenticator app.

### Task C: Exception Handling
**Goal:** Prevent the ugly 500 error page.
**Action:** Catch the failure gracefully.
* If validation fails, return `back()->withErrors(['code' => 'The authentication code is invalid. Please try again or rescan the QR code.']);` instead of letting the exception crash the flow if it's not automatically handled by FormRequest.

## 3. Desired Code Output
Please generate:
1.  **Refactored `TwoFactorSetup.vue`**: Full template with fixed SVG styling and Tailwind classes.
2.  **Updated `TwoFactorController.php`**: specifically the `confirm` method with the "Window" fix and input sanitization.