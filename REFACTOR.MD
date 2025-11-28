# FRANCIS PROJECT REFACTORING & UPGRADE PLAN

This document serves as the master plan for upgrading and refactoring the Francis Build Manage system.
**INSTRUCTIONS FOR CURSOR (AI):**
1. Read this file to understand the current state.
2. Pick the **first unchecked task** (`- [ ]`) from the list below.
3. Execute the specific **Prompt** defined in that task using the provided **Context**.
4. After successfully completing the code changes, **UPDATE this file** by marking the task as completed (`- [x]`) and move to the next one if requested.
5. Do NOT create new markdown files. Keep all tracking here.

---

## Phase 1: Framework Upgrade & Dependencies
- [x] **Task 1: Upgrade to Laravel 12**
    - **Context:** `@composer.json`, `@composer.lock`, `@package.json`
    - **Prompt:**
        > Act as a Laravel DevOps expert. Analyze `composer.json` (currently Laravel 11).
        > 1. Update `laravel/framework` and other core dependencies (collision, phpunit, etc.) to versions compatible with **Laravel 12**.
        > 2. Check `package.json` for any build tool updates required for the new version.
        > 3. Provide the terminal commands required to apply these changes (e.g., `composer update`).
        > 4. Ensure no custom packages are removed unless strictly incompatible.
    - **Notes:** Bumped `laravel/framework` to `^12.0`, refreshed first-party packages (Sanctum, Tinker, Sail, Pint, Collision, PHPUnit) and aligned Vite/Tailwind stack in `package.json` with Laravel 12 scaffolding. Regenerated `composer.lock` and `package-lock.json`.
    - **Commands Executed:** `composer update`, `npm install`

---

## Phase 2: UI/UX Redesign (SPA & Layout)
- [x] **Task 2.1: Layout Architecture & SPA Conversion**
    - **Context:** `@resources/js/Layouts/AuthenticatedLayout.vue`, `@resources/js/app.js`, `@resources/css/app.css`
    - **Prompt:**
        > Act as a Senior Frontend Developer (Vue.js/Tailwind). Redesign the application layout:
        > 1. **Sidebar Navigation:** Replace the current top bar with a modern, collapsible Sidebar Navigation (Left-side).
        > 2. **SPA Enforcement:** Scan the layout and ensure all navigation links use `<Link>` from Inertia.js instead of standard `<a>` tags to prevent page reloads.
        > 3. **Visual Style:** Apply a professional color palette (e.g., Slate/Indigo) using Tailwind CSS classes. Use a "Card-based" layout for the main content area with soft shadows.
    - **Notes:** Rebuilt `AuthenticatedLayout.vue` around a slate/indigo sidebar shell with collapsible widths, centralized the nav config, and rendered each entry via Inertia `<Link>`s to guarantee SPA navigation. Elevated the content area into a card-style container with soft shadows and set a slate base in `app.css` for consistent theming.

- [x] **Task 2.2: Mobile Responsiveness**
    - **Context:** `@resources/js/Layouts/AuthenticatedLayout.vue`, `@tailwind.config.js`
    - **Prompt:**
        > Enhance the responsive behavior of the new `AuthenticatedLayout.vue`:
        > 1. **Mobile Menu:** Implement an Off-canvas (slide-in) menu for mobile screens, ensuring it contains the same items as the desktop sidebar.
        > 2. **Breakpoints:** Optimize the layout for large screens (`2xl`) and tablets (`md`/`lg`).
        > 3. Ensure the hamburger menu trigger is easily accessible on touch devices.
    - **Notes:** Added dedicated mobile sidebar state with overlay + slide-in panel mirroring desktop navigation, wired the header hamburger to control it, and tuned spacing (`lg`, `2xl`) for the card container and header. Also introduced light fade transitions in `app.css` for the overlay.
    - **Additional Tweaks:** Disabled Tailwind's auto dark-mode (`darkMode: 'class'`) so the app stays on the new light palette, refreshed all shared components/pages to drop legacy `dark:` classes, and rebuilt the MySite data table with improved readability plus the new loading animation on build actions.

- [x] **Task 2.3: Fix Layout Bugs & Polish UI**
    - **Context:** `@resources/js/Layouts/AuthenticatedLayout.vue`, `@resources/css/app.css`
    - **Prompt:**
        > Act as a Frontend Expert. Fix the following specific UI issues:
        > 1. **Page Height Issue:** The page has excessive whitespace at the bottom. Ensure the main layout container uses `min-h-screen` and `flex flex-col`, with the main content area using `flex-1` to push the footer (if any) to the bottom naturally. Remove fixed `h-screen` if it causes overflow issues.
        > 2. **Sidebar Icons:** The menu icons look distorted/squashed when the menu is collapsed. Add `shrink-0` class to all sidebar icons to prevent flex compression. Ensure they have a fixed width/height (e.g., `w-6 h-6`).
        > 3. **Modern Aesthetic:** Improve the visual hierarchy.
        >     - Add subtle `transition-all duration-300` to buttons and links.
        >     - Use a "Glassmorphism" effect for the Sidebar or Topbar (backdrop-blur-md, bg-opacity-90).
        >     - Ensure consistency in padding/margins across all Dashboard cards.
    - **Notes:** Fixed all sidebar icons by adding `shrink-0` class and increasing size to `w-6 h-6` to prevent distortion when collapsed. Added glassmorphism effects to sidebar (`bg-slate-900/95 backdrop-blur-md`) and header (`bg-white/90 backdrop-blur-md`). Applied `transition-all duration-300` to all interactive elements (buttons, links, nav items). Made header sticky with `sticky top-0 z-30` for better UX. Improved hover states with enhanced visual feedback. Added custom transition utilities in `app.css` for consistent smooth animations. Ensured consistent padding across breakpoints in main content area. Fixed variable reference bug (`sidebarCollapsed` → `desktopSidebarCollapsed`).
    - **Additional Enhancements:**
        - Applied gradient backgrounds to sidebars with indigo accent overlays for depth
        - Enhanced navigation items with gradient active states and glow effects (ring-2 ring-indigo-400/20)
        - Upgraded all buttons (Primary, Secondary, Danger) with gradients, hover lift effects, and better shadows
        - Improved form components (TextInput, Checkbox, InputLabel, InputError) with better focus states and styling
        - Enhanced Dropdown with glassmorphism, rounded corners (rounded-2xl), and better shadows
        - Added custom scrollbar styling to navigation areas (custom-scrollbar class)
        - Upgraded Modal with backdrop blur and enhanced card styling
        - Added gradient background to main content area (from-slate-50 via-slate-100 to-slate-50)
        - Enhanced ApplicationLogo with SVG gradient and hover scale effect
        - Created extensive CSS utilities: fade-in, slide-in-left, shimmer, pulse-soft, card-hover, hover-scale
        - Improved antialiasing and font smoothing across the app
        - Added smooth scroll behavior and better text rendering
        - Dashboard page layout simplified with cleaner card structure

- [x] **Task 2.4: Modernize All CRUD Pages UI**
    - **Context:** `@resources/js/Pages/Users/`, `@resources/js/Pages/EnvVariables/`, `@resources/js/Pages/Logs/`
    - **Notes:** Extended the beautiful UI design to all CRUD pages for consistency:
        - **Users Management:**
            - Index: Modern table with gradient header, user avatars, role badges, icon-based actions
            - Create: Card-based form with gradient header, better input styling, icons
            - Edit: Matching create page design with update-focused actions
            - Added empty states with helpful messaging
        - **Environment Variables:**
            - Split view with add form card and variables list table
            - Gradient headers, monospace font for code values
            - Enhanced action buttons with icons
            - Success notifications with gradient backgrounds
        - **Logs Manager:**
            - Breadcrumb navigation for folder hierarchy
            - Grid-based folder cards with hover effects
            - File list with view/download actions
            - Empty state with helpful icons
        - **Global Improvements:**
            - All tables use consistent styling with hover states
            - Icon integration throughout for better visual context
            - Gradient accent colors (indigo/purple)
            - Rounded corners (rounded-2xl) for modern look
            - Better spacing and typography
            - Smooth transitions and hover effects
            - Empty states with SVG illustrations
            - Action buttons with icon + text labels

---

## Phase 3: Backend Refactoring (SOLID & Services)
- [x] **Task 3.1: Dynamic Parameters System (Database)**
    - **Context:** `@database/migrations`, `@routes/web.php`, `@app/Models`
    - **Prompt:**
        > Act as a Senior Backend Developer. We need to remove hardcoded values.
        > 1. Create a migration for a `parameters` table: `id`, `key` (unique string), `value` (text), `type` (string), `description` (nullable).
        > 2. Create a `Parameter` Model and a `ParameterController` with full CRUD operations (Index, Store, Update, Destroy).
        > 3. Register the resource routes in `web.php` under the `admin` middleware group.
    - **Notes:** Added `create_parameters_table` migration with the required schema, introduced `Parameter` model + resource controller that exposes JSON CRUD (index/store/update/destroy with validation), and registered the resource routes inside the admin-protected group in `web.php`.

- [x] **Task 3.2: Refactor Hardcoded Values & Implement Service Pattern**
    - **Context:** `@app/Http/Controllers/MySiteController.php`, `@app/Services/SiteBuildService.php`, `@app/Models/Parameter.php`
    - **Prompt:**
        > Refactor `MySiteController.php` to follow SOLID principles:
        > 1. **Extract Logic:** Create `app/Services/SiteBuildService.php`. Move the logic for `buildMySite`, `store`, and `generateShellScript` into this service.
        > 2. **Remove Hardcoding:** Replace hardcoded paths (e.g., `/var/www/html`) and PM2 ports in the code with calls to the `Parameter` model (created in Task 3.1) or config files.
        > 3. **Dependency Injection:** Inject `SiteBuildService` into `MySiteController` instead of using static calls or raw logic in the controller.

---

## Phase 4: Utilities Refactoring
- [x] **Task 4: Env Manager Refactoring**
    - **Context:** `@app/Services/EnvManagerService.php`, `@app/Http/Controllers/MySiteController.php`
    - **Prompt:**
        > Extract the `.env` file generation logic into a dedicated class `app/Services/EnvManagerService.php`.
        > **Requirements:**
        > 1. Method `updateOrCreateEnv($path, array $data)`.
        > 2. **Safety:** Check if file exists. If a key exists, update it properly preserving comments. If not, append it.
        > 3. **Validation:** Ensure values with spaces/special characters are double-quoted.

        **Notes / Implementation Summary:**

        - Implemented `app/Services/EnvManagerService.php` with `updateOrCreateEnv(string $path, array $data)`.
        - Safety improvements:
            - Creates a timestamped backup of existing `.env` (e.g., `.env.20251128_150102.bak`) before any writes.
            - Writes via a temporary file and `rename()` for atomic replace.
            - Attempts to preserve original file permissions (records `fileperms()` and restores after rename).
            - Attempts to `chmod()` to make the file writable if necessary, and throws a descriptive exception if still not writable.
            - Properly quotes/escapes values containing spaces, quotes, dollar signs, or newlines.
            - Updates existing keys in-place while preserving comments and ordering where possible; appends new keys at the end.

        - Tests added:
            - `tests/Unit/EnvManagerServiceTest.php` (basic update/append case).
            - `tests/Unit/EnvManagerServiceSpecialCharsTest.php` (spaces, quotes, dollars, newlines).
            - `tests/Unit/EnvManagerServiceUnicodeLongTest.php` (unicode and very long values + backup existence).

        - All Unit tests pass locally: `OK (4 tests, 10 assertions)`.

        **Recommendation / Next:**

        - Consider adding a monitoring/log entry when `.env` writes fail in production, and a dry-run mode for `SiteBuildService` to show the planned `.env` changes without applying them.
        - Task considered complete from a development and test coverage perspective; documentation updated here.

---