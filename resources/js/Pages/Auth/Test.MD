# GITHUB COPILOT INSTRUCTIONS: MY SITE REFACTOR & DYNAMIC RBAC

## 1. Context
We are refactoring the "My Site" module for better UX and implementing a data-driven Role-Based Access Control (RBAC) system populated via a JSON file.
We also need to implement strict logic for User Management to prevent privilege escalation.

## 2. Task Breakdown

### Task A: Refactor "My Site" UI/UX
**Target File:** `resources/js/Pages/MySites/Index.vue`, `MySiteController.php`
**Requirements:**
1.  **Table Structure Update:**
    * **ID Column:** Make it a clickable link. Redirects to `route('my-site.show', id)`.
    * **Action Column:**
        * Remove "History" button.
        * **"Details" Button:** Opens a Modal showing site config (Repository, Branch, Port, etc.) in **Read-Only** mode.
        * **"Edit" Button:** Opens a Modal (reuse Create Modal logic or new component) to update site info.
        * **"Delete" Button:** Keep existing logic (Confirmation -> Delete).
2.  **Detail Page (`Show.vue`):**
    * Created when clicking ID.
    * **Main Content:** Display a list/table of **Build Histories** for this site.
    * **Interactivity:** Clicking a history record shows the specific logs for that build (in a modal or expanded row).
3.  **Edit Functionality:**
    * Implement `update` method in `MySiteController`.
    * Ensure the Frontend Edit Modal populates existing data correctly.

### Task B: Dynamic Permission System (RBAC)
**Target:** Database, Commands, Middleware.
**Goal:** Permissions are defined in a JSON file and loaded into the DB.

#### 1. Database Schema
Create a migration for table `role_permissions`:
* `id` (primary key)
* `role` (string: 'user', 'admin', 'super_admin')
* `permission` (string: e.g., 'view_mysites', 'create_mysites')
* `created_at`, `updated_at`

#### 2. The JSON Source (`database/data/role_rights.json`)
Create this file with the following logic:
* **user**: Can ONLY view Dashboard/Profile. Explicit DENY on: Parameters, MySites, EnvVariables, User Management.
* **admin**:
    * Can: View Dashboard, View MySites List, Trigger Build (MySites), Manage EnvVariables, View Users, Manage Users (Restricted).
    * Cannot: Access Parameters, Create/Edit/Delete MySites.
* **super_admin**: Wildcard `*` (All permissions).

#### 3. Import Command (`ImportRoleRightsCommand`)
Create an Artisan command: `php artisan app:import-rights`.
* **Logic:**
    1.  Truncate `role_permissions` table.
    2.  Read `database/data/role_rights.json`.
    3.  Insert new rows.

#### 4. Middleware / Gates Implementation
Refactor `RoleMiddleware` or create a new `CheckPermission` middleware/Gate.
* **Key Permissions:**
    * `view_parameters`
    * `view_mysites`, `manage_mysites` (Create/Edit/Delete), `build_mysites`
    * `view_env_variables`, `manage_env_variables`
    * `view_users`, `manage_users` (Edit/Update/Delete Users)

### Task C: Apply Permissions to MySite & Menus
**1. My Site Logic (Admin Constraint):**
* Admin can access `MySiteController@index` (List) and `MySiteController@build`.
* Admin CANNOT access `store`, `update`, `destroy`.
* **Frontend:** Hide "Create Site", "Edit", and "Delete" buttons if the user does not have `manage_mysites`.

**2. Menu/Sidebar Visibility:**
* Hide "Parameters" if user lacks `view_parameters`.
* Hide "My Sites" if user lacks `view_mysites`.
* Hide "Env Variables" if user lacks `view_env_variables`.

### Task D: User Management Constraints (Strict Rules)
**Target:** `UserController.php` (update/edit/destroy methods).
**Logic:** Even if a user has `manage_users` permission, we must enforce rank-based constraints.

**Rules:**
1.  **Super Admin**:
    * Can edit/change role for **ANY** user (including other Super Admins or Admins).
2.  **Admin**:
    * Can **ONLY** edit/change role for users with role `user` (Lower Rank).
    * **CANNOT** edit/change role for other `admin` (Peer Rank).
    * **CANNOT** edit/change role for `super_admin` (Higher Rank).
    * **CANNOT** promote a `user` to `admin` or `super_admin` (Can only assign/maintain 'user' role).
    * *Implementation Detail:* In `update` method, check:
        ```php
        if (Auth::user()->isAdmin()) {
            // Check Target: Cannot touch Admin or Super Admin
            if ($targetUser->hasAdminPrivileges()) {
                abort(403, 'You cannot edit an Admin or Super Admin.');
            }
            // Check Input: Cannot assign Admin or Super Admin role
            if (in_array($request->role, [User::ROLE_ADMIN, User::ROLE_SUPER_ADMIN])) {
                abort(403, 'You cannot promote a user to Admin level.');
            }
        }
        ```
3.  **Frontend (Users/Index.vue & Edit.vue):**
    * Hide "Edit" / "Delete" buttons for rows where the target user is an Admin/Super Admin **IF** the current logged-in user is only an Admin.

## 3. Execution Steps for Copilot
Please generate code in this order:
1.  **The Migration** for `role_permissions`.
2.  **The JSON File** (`role_rights.json`) covering all roles including the new user management rules.
3.  **The Artisan Command** to import the rights.
4.  **The Gates/Middleware** setup.
5.  **Refactor `UserController.php`** to implement the **Strict Rules** defined in Task D.
6.  **Refactor `MySiteController`** and **Vue Components** for Task A & C.