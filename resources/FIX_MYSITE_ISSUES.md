# MYSITE & SYSTEM FIX PLAN

This document outlines the tasks required to fix bugs in the "My Site" module, implement the Log History stream, standardizing notifications, and perform a security audit.

**INSTRUCTIONS FOR CURSOR:**
1.  Read this file completely.
2.  Execute tasks sequentially.
3.  Do NOT create new plan files. Update code directly.

---

## Task 1: Fix "Last Builder" Name Display
- **Issue:** The `last_builder` column in `MySites/Index.vue` shows an ID or is empty instead of the user's name.
- **Context:** `@app/Models/MySite.php`, `@app/Http/Controllers/MySiteController.php`, `@resources/js/Pages/MySites/Index.vue`
- **Prompt:**
    > Fix the "Last Builder" display issue:
    > 1.  **Model:** In `app/Models/MySite.php`, ensure the `lastBuilder` relationship is defined: `return $this->belongsTo(User::class, 'last_user_build');`.
    > 2.  **Controller:** In `MySiteController@index`, ensure you are eager loading this relationship: `->with('lastBuilder')`.
    > 3.  **Frontend:** In `MySites/Index.vue`, update the table column to display `site.last_builder?.name` instead of the raw ID. Check for null values gracefully.

---

## Task 2: Implement Log History Stream (Popup)
- **Issue:** Clicking "History" does nothing. Needs to list log files from the server storage for that site.
- **Context:** `@app/Http/Controllers/LogPM2Controller.php`, `@resources/js/Pages/Utils/LogHistoryModal.vue`, `@routes/web.php`
- **Prompt:**
    > Implement the Log History feature that scans the file system for logs.
    > 1.  **Backend Logic:**
    >     - Create/Update `LogPM2Controller@getSiteLogs($siteId)`.
    >     - Logic: Look into the log directory (e.g., `storage/logs/sites/{site_id}/` or wherever build logs are stored). Use `File::files()` or `glob()` to list all `.log` files sorted by date (newest first).
    >     - Return a JSON list: `[{ filename: 'build-2023-10-01.log', date: '...', path: '...' }]`.
    > 2.  **Route:** Define a GET route `/my-sites/{id}/logs` linking to this method.
    > 3.  **Frontend (`LogHistoryModal.vue`):**
    >     - When the modal opens, fetch this list via Axios.
    >     - Display a table of logs.
    >     - **Action:** Clicking a log entry should open a "Log Viewer" (stream the content of that specific file).

---

## Task 3: Fix Site Deletion Flow
- **Issue:** The Delete confirmation appears, but clicking confirm does not delete the site.
- **Context:** `@resources/js/Pages/MySites/Index.vue`, `@app/Http/Controllers/MySiteController.php`, `@app/Services/SiteDestructionService.php`
- **Prompt:**
    > Debug and Fix the Delete Site functionality.
    > 1.  **Frontend:** Check `MySites/Index.vue`. Ensure the "Confirm" button in the Delete Modal triggers a `form.delete(route('my-site.destroy', site.id))`. Verify `useForm` is used correctly.
    > 2.  **Backend:** Check `MySiteController@destroy`.
    >     - Ensure it authorizes the action (Super Admin only).
    >     - Call `SiteDestructionService` to handle the cleanup (files, PM2, DB).
    > 3.  **Service:** In `SiteDestructionService`, ensure `exec` commands (like `pm2 delete`) are wrapped in `try-catch` so that if one fails (e.g., app not running), the DB deletion still proceeds.
    > 4.  **Feedback:** Return a proper Redirect with Flash message upon success.

---

## Task 4: Standardize Toast Notifications
- **Issue:** Alerts are inconsistent. Need a global Toast system.
- **Context:** `@resources/js/app.js`, `@resources/js/Layouts/AuthenticatedLayout.vue`, `@app/Http/Middleware/HandleInertiaRequests.php`
- **Prompt:**
    > Implement a consistent Toast Notification system.
    > 1.  **Middleware:** In `HandleInertiaRequests.php`, ensure `flash` messages (`success`, `error`, `warning`) are shared to Vue props.
    > 2.  **Frontend Component:**
    >     - Install/Use a Toast library (like `vue-toastification`) OR create a custom `Toast.vue` component.
    >     - In `AuthenticatedLayout.vue` (or `app.js`), watch `page.props.flash`. If a message arrives, trigger the Toast display automatically.
    > 3.  **Usage:** Ensure all Controllers (MySite, User, etc.) use `return redirect()->back()->with('success', 'Message here');`.

---

## Task 5: Security Audit & Code Cleanup
- **Issue:** Need to ensure code safety, clean routing, and CSRF/CSJ standards.
- **Context:** `@routes/web.php`, `@app/Http/Controllers/`, `@app/Services/SiteBuildService.php`
- **Prompt:**
    > Perform a Security and Code Quality Audit.
    > 1.  **Route Security:** In `web.php`, verify that sensitive routes (Delete, Parameters, Env) are protected by `auth` and appropriate `role` middleware (Super Admin).
    > 2.  **Shell Injection Prevention:** Check `SiteBuildService.php` and `SiteDestructionService.php`. Ensure all user inputs (folder paths, names) passed to `exec()` or `shell_exec()` are sanitized using `escapeshellarg()`.
    > 3.  **Validation:** Scan Controllers (`store`/`update` methods). Ensure strict validation rules are applied to all inputs.
    > 4.  **Cleanup:** Remove any `dd()`, `console.log`, or commented-out old code blocks found in the source.