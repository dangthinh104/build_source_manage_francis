# GITHUB COPILOT INSTRUCTIONS: RBAC MATRIX UI & CODEMIRROR INTEGRATION

## 1. Context & Goals
We are enhancing the application with two specific UI/UX improvements:
1.  **RBAC Matrix Page:** A dedicated, read-only dashboard to visualize the permissions assigned to each role (Super Admin, Admin, User).
2.  **Professional Code Editor:** Integrating **CodeMirror** into the Environment Variables manager for better JSON/Text editing.

## 2. Prerequisites
Ensure the following packages are installed:
```bash
npm install vue-codemirror @codemirror/lang-json

3. Tasks Breakdown

Task 1: RBAC Permission Matrix (Read-Only UI)

Goal: Create a page where Admins can view (but not edit) the current Role-Permission mapping.

A. Backend Logic

    Controller: Create RbacController.

    Method index:

        Fetch all roles and their associated permissions from the role_permissions table (or read directly from database/data/role_rights.json if simpler).

        Format the data into a "Matrix" structure for the frontend:
        JSON

        {
          "permissions": ["view_dashboard", "manage_mysites", ...],
          "roles": {
            "super_admin": ["*"],
            "admin": ["view_dashboard", ...],
            "user": ["view_dashboard"]
          }
        }

        Return Inertia::render('Rbac/Index', [...]).

    Route: Add Route::get('/rbac/matrix', [RbacController::class, 'index'])->name('rbac.matrix'); in web.php.

        Middleware: Ensure only super_admin (or authorized admins) can access this route.

B. Frontend UI (resources/js/Pages/Rbac/Index.vue)

    Layout: Use AuthenticatedLayout.

    Component Structure:

        Header: "Role Permissions Matrix".

        Table:

            Rows: List of unique Permissions.

            Columns: The Roles (User, Admin, Super Admin).

        Cell Content:

            If the role has the permission: Show a Green Check Icon (✅).

            If not: Show a Gray Dash/X Icon (⛔).

            Special Case: If permission is * (Wildcard), show "Full Access" badge for all rows.

    Styling: Use Tailwind CSS for a clean, striped table design.

C. Sidebar Navigation

    Update AuthenticatedLayout.vue: Add a "Permissions" link in the sidebar, visible only to users with the view_rbac (or super_admin) capability.

Task 2: Implement CodeMirror for Env Manager

Goal: Replace standard inputs with a code editor for better DX.

A. resources/js/Pages/EnvVariables/EditModal.vue

    Import: import { Codemirror } from 'vue-codemirror'; import { json } from '@codemirror/lang-json';

    Template Replacement:

        Remove the <input v-model="form.variable_value">.

        Insert <Codemirror> component:

            v-model="form.variable_value"

            :extensions="[json()]"

            placeholder="Enter value..."

            Style: Height 250px, Border rounded-md, Focus ring matching Tailwind theme.

    Layout: Increase Modal width (sm:max-w-2xl) to accommodate the editor.

B. resources/js/Pages/EnvVariables/Index.vue

    Add Form Update:

        Locate the "Add Environment Variable" form.

        Replace the "Value" text input with <Codemirror> (same configuration as above).

        Ensure the editor container spans the full width (col-span-2) for better usability.

4. Execution Plan for Copilot

Please generate code in the following order:

    Task 2 (CodeMirror): Update the EnvVariables Vue components first as this is a direct component swap.

    Task 1 (RBAC Backend): Create the RbacController and Routes.

    Task 1 (RBAC Frontend): Create the Rbac/Index.vue page and update the Sidebar.