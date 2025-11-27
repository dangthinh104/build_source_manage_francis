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
    - **Additional Tweaks:** Disabled Tailwind’s auto dark-mode (`darkMode: 'class'`) so the app stays on the new light palette, refreshed all shared components/pages to drop legacy `dark:` classes, and rebuilt the MySite data table with improved readability plus the new loading animation on build actions.

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

- [ ] **Task 3.2: Refactor Hardcoded Values & Implement Service Pattern**
    - **Context:** `@app/Http/Controllers/MySiteController.php`, `@app/Services/SiteBuildService.php`, `@app/Models/Parameter.php`
    - **Prompt:**
        > Refactor `MySiteController.php` to follow SOLID principles:
        > 1. **Extract Logic:** Create `app/Services/SiteBuildService.php`. Move the logic for `buildMySite`, `store`, and `generateShellScript` into this service.
        > 2. **Remove Hardcoding:** Replace hardcoded paths (e.g., `/var/www/html`) and PM2 ports in the code with calls to the `Parameter` model (created in Task 3.1) or config files.
        > 3. **Dependency Injection:** Inject `SiteBuildService` into `MySiteController` instead of using static calls or raw logic in the controller.

---

## Phase 4: Utilities Refactoring
- [ ] **Task 4: Env Manager Refactoring**
    - **Context:** `@app/Services/EnvManagerService.php`, `@app/Http/Controllers/MySiteController.php`
    - **Prompt:**
        > Extract the `.env` file generation logic into a dedicated class `app/Services/EnvManagerService.php`.
        > **Requirements:**
        > 1. Method `updateOrCreateEnv($path, array $data)`.
        > 2. **Safety:** Check if file exists. If a key exists, update it properly preserving comments. If not, append it.
        > 3. **Validation:** Ensure values with spaces/special characters are double-quoted.
        > 4. **Error Handling:** Throw specific exceptions if file permissions deny writing, so the Controller can catch and display a user-friendly error.

---