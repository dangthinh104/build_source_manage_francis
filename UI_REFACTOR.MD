## Phase 2: UI/UX Refinement (Fixes & Modernization)
- [ ] **Task 2.3: Fix Layout Bugs & Polish UI**
    - **Context:** `@resources/js/Layouts/AuthenticatedLayout.vue`, `@resources/js/Components/Sidebar.vue` (if exists), `@resources/css/app.css`
    - **Prompt:**
        > Act as a Frontend Expert. Fix the following specific UI issues:
        > 1.  **Page Height Issue:** The page has excessive whitespace at the bottom. Ensure the main layout container uses `min-h-screen` and `flex flex-col`, with the main content area using `flex-1` to push the footer (if any) to the bottom naturally. Remove fixed `h-screen` if it causes overflow issues.
        > 2.  **Sidebar Icons:** The menu icons look distorted/squashed when the menu is collapsed. Add `shrink-0` class to all sidebar icons to prevent flex compression. Ensure they have a fixed width/height (e.g., `w-6 h-6`).
        > 3.  **Modern Aesthetic:** Improve the visual hierarchy.
        >     - Add subtle `transition-all duration-300` to buttons and links.
        >     - Use a "Glassmorphism" effect for the Sidebar or Topbar (backdrop-blur-md, bg-opacity-90).
        >     - Ensure consistency in padding/margins across all Dashboard cards.

---

## Phase 3: Backend Refactoring & API (Enhanced)
*(Existing Task 3.1 and 3.2 remains... add this new Task 3.3)*

- [ ] **Task 3.3: API Layer Implementation**
    - **Context:** `@routes/api.php`, `@app/Http/Controllers/Api/`, `@app/Http/Resources/`
    - **Prompt:**
        > We need to expose clear API endpoints for external integrations, separate from the Inertia frontend.
        > 1.  **API Structure:** Create a new namespace `App\Http\Controllers\Api`.
        > 2.  **API Resources:** Create Laravel API Resources (e.g., `UserResource`, `SiteResource`) to transform models into standardized JSON responses.
        > 3.  **Endpoints:** In `routes/api.php`, create routes protected by Sanctum (`auth:sanctum`) for:
        >     - `GET /api/me` (Current user profile)
        >     - `GET /api/sites` (List sites)
        >     - `POST /api/sites/{id}/build` (Trigger build - reusing the `SiteBuildService` logic created in Task 3.2).
        > 4.  **Swagger/OpenAPI (Optional):** Add a simple comment block or README section describing these 3 key endpoints for developers.

---

## Phase 5: Security Features (New)
- [ ] **Task 5.1: Google 2FA Implementation**
    - **Context:** `@composer.json`, `@app/Models/User.php`, `@routes/web.php`, `@app/Http/Controllers/Auth/TwoFactorController.php`
    - **Prompt:**
        > Implement Two-Factor Authentication (2FA) using Google Authenticator.
        > 1.  **Package:** Install `pragmarx/google2fa-laravel` (or suggested compatible package for Laravel 12).
        > 2.  **Database:** Create a migration to add `google2fa_secret` (nullable text) and `google2fa_enabled` (boolean) to the `users` table.
        > 3.  **Backend Logic:**
        >     - Create `TwoFactorController` with methods: `enable` (generate secret/QR code), `verify` (check OTP), and `disable`.
        >     - Create a Middleware `TwoFactorMiddleware` that intercepts requests after login. If user has 2FA enabled but hasn't verified OTP in session, redirect to a generic OTP entry page.
        > 4.  **Frontend (Inertia):**
        >     - Create `Pages/Auth/TwoFactorChallenge.vue` (Enter OTP).
        >     - Create `Pages/Profile/Partials/TwoFactorForm.vue` (Show QR Code & Enable button).
        > 5.  **Integration:** Register the middleware in `bootstrap/app.php` (for Laravel 11/12) and apply it to the `dashboard` routes.