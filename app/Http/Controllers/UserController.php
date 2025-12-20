<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class UserController extends Controller
{

    /**
     * Check ROLE access
     */
    public function __construct()
    {
        if (!auth()->user() || !auth()->user()->hasAdminPrivileges()) {
            abort(403);
        }
    }

    //=====================================================
    // GROUP METHOD SHOW
    //=====================================================
    public function index(Request $request)
    {
        $query = User::query();

        if ($request->has('name')) {
            $query->where('name', 'like', '%' . $request->input('name') . '%');
        }

        if ($request->has('email')) {
            $query->where('email', 'like', '%' . $request->input('email') . '%');
        }

        $users = $query->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Users/Index', [
            'users' => $users,
            'filters' => $request->only(['name', 'email']),
            'can_manage_users' => auth()->user()->isSuperAdmin(),
        ]);
    }

    public function create()
    {
        $currentUser = auth()->user();
        
        // Determine which roles the current user can assign
        $availableRoles = [
            ['value' => User::ROLE_USER, 'label' => 'User'],
        ];
        
        // Only super admin can assign admin or super_admin roles
        if ($currentUser->isSuperAdmin()) {
            $availableRoles[] = ['value' => User::ROLE_ADMIN, 'label' => 'Administrator'];
            $availableRoles[] = ['value' => User::ROLE_SUPER_ADMIN, 'label' => 'Super Admin'];
        }

        return Inertia::render('Users/Create', [
            'availableRoles' => $availableRoles,
        ]);
    }

    public function edit($id)
    {
        $targetUser = User::findOrFail($id);
        $currentUser = auth()->user();

        // Rank-based constraints: Admin cannot edit Admin or Super Admin
        if ($currentUser->isAdmin() && !$currentUser->isSuperAdmin()) {
            if ($targetUser->hasAdminPrivileges()) {
                abort(403, 'You cannot edit an Admin or Super Admin user.');
            }
        }

        return Inertia::render('Users/Edit', [
            'user' => $targetUser,
        ]);
    }

    //=====================================================
    // GROUP METHOD PROCESS
    //=====================================================

    public function store(Request $request)
    : RedirectResponse {

        $currentUser = auth()->user();

        $validated = $request -> validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role'     => 'required|in:' . User::ROLE_USER . ',' . User::ROLE_ADMIN . ',' . User::ROLE_SUPER_ADMIN,
        ]);

        // SECURITY: Restrict role assignment based on current user's role
        // Only Super Admin can create Admin or Super Admin users
        if (!$currentUser->isSuperAdmin()) {
            if (in_array($validated['role'], [User::ROLE_ADMIN, User::ROLE_SUPER_ADMIN])) {
                return redirect()->route('users.create')
                    ->with('error', 'You do not have permission to create Admin or Super Admin users.');
            }
            // Force role to 'user' for non-super-admin creators
            $validated['role'] = User::ROLE_USER;
        }

        // Generate 2FA secret by default for new users
        $secret = bin2hex(random_bytes(32));

        User ::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => bcrypt($validated['password']),
            'role'     => $validated['role'],
            'two_factor_secret' => encrypt($secret),
        ]);

        return redirect() -> route('users.index') -> with('success', 'User added successfully! 2FA is enabled by default.');
    }



    public function update(Request $request, $id)
    : RedirectResponse {

        $targetUser = User::findOrFail($id);
        $currentUser = auth()->user();

        // Rule B: Super Admin Protection
        // Prevent editing super_admin users via UI (except self for name/email only)
        if ($targetUser->isSuperAdmin()) {
            // Allow super_admin to update their own name/email (not role)
            if ($currentUser->id === $targetUser->id) {
                $request->validate([
                    'name'  => 'required|string|max:255',
                    'email' => 'required|string|email|max:255|unique:users,email,' . $targetUser->id,
                ]);
                
                // Super admin cannot change their own role via this controller
                $targetUser->update($request->only('name', 'email'));
                return redirect()->route('users.index')->with('success', 'Profile updated successfully');
            }
            
            // Other users (even super_admins) cannot edit super_admin accounts
            return redirect()->route('users.index')->with('error', 'Super Admin accounts cannot be modified via UI. Use Console Commands.');
        }

        // Validate incoming data
        $request->validate([
            'name'  => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $targetUser->id,
            'role'  => 'nullable|in:' . User::ROLE_USER . ',' . User::ROLE_ADMIN . ',' . User::ROLE_SUPER_ADMIN,
        ]);

        // Rank-based constraints for non-super-admin editors (Admin)
        if ($currentUser->isAdmin() && !$currentUser->isSuperAdmin()) {
            // Admin users have restrictions
            
            // Check 1: Cannot edit Admin or Super Admin users
            if ($targetUser->hasAdminPrivileges()) {
                return redirect()->route('users.index')->with('error', 'You cannot edit an Admin or Super Admin user.');
            }

            // Rule A: Only super_admin can change roles
            // Admin cannot change anyone's role - filter out role field
            $targetUser->update($request->only('name', 'email'));
            return redirect()->route('users.index')->with('success', 'User updated successfully');
        }

        // Super Admin updating non-super-admin user: Full access (can change role)
        $updateData = $request->only('name', 'email');
        if ($request->has('role') && $currentUser->isSuperAdmin()) {
            $updateData['role'] = $request->input('role');
        }
        
        $targetUser->update($updateData);

        return redirect()->route('users.index')->with('success', 'User updated successfully');
    }

    public function destroy($id) {
        $targetUser = User::findOrFail($id);
        $currentUser = auth()->user();

        // Rule B: Super Admin Protection - Cannot delete super_admin via UI
        if ($targetUser->isSuperAdmin()) {
            return response()->json([
                'status' => false,
                'message' => 'Super Admin accounts cannot be deleted via UI. Use Console Commands.'
            ], 403);
        }

        // Prevent self-deletion
        if ($currentUser->id === $targetUser->id) {
            return response()->json([
                'status' => false,
                'message' => 'You cannot delete your own account.'
            ], 403);
        }

        // Rank-based constraints for deletion
        if ($currentUser->isAdmin() && !$currentUser->isSuperAdmin()) {
            // Admin users can only delete regular users
            if ($targetUser->hasAdminPrivileges()) {
                return response()->json([
                    'status' => false,
                    'message' => 'You cannot delete an Admin or Super Admin user.'
                ], 403);
            }
        }

        $targetUser->delete();

        return response()->json([
            'status' => true,
            'message' => 'User deleted successfully'
        ]);
    }

    /**
     * Toggle two-factor authentication for a user.
     * Only accessible by Super Admin.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleTwoFactor(Request $request, $id)
    {
        // Strictly ensure ONLY super admin can toggle 2FA
        if (!auth()->user()->isSuperAdmin()) {
            return response()->json([
                'status' => false,
                'message' => 'Forbidden. Only Super Admin can manage 2FA.'
            ], 403);
        }

        $user = User::findOrFail($id);

        $request->validate([
            'enable' => 'required|boolean',
        ]);

        if ($request->input('enable')) {
            // Enable 2FA - Generate a generic secret
            // User will need to scan QR code on next login
            $secret = bin2hex(random_bytes(32)); // Generate a 64-character hex secret
            $user->two_factor_secret = encrypt($secret);
            $user->save();

            return response()->json([
                'status' => true,
                'message' => '2FA enabled for user. User must scan QR code on next login.',
            ]);
        } else {
            // Disable 2FA
            $user->two_factor_secret = null;
            $user->two_factor_recovery_codes = null;
            $user->two_factor_confirmed_at = null;
            $user->save();

            return response()->json([
                'status' => true,
                'message' => '2FA disabled for user.',
            ]);
        }
    }

    /**
     * Force reset/disable two-factor authentication for a user (Super Admin only).
     */
    public function resetTwoFactor(User $user)
    : RedirectResponse {

        // Ensure the acting user is Super Admin
        if (!auth()->user() || !auth()->user()->isSuperAdmin()) {
            abort(403);
        }

        // Clear 2FA fields for the target user
        $user->two_factor_secret = null;
        $user->two_factor_confirmed_at = null;
        $user->two_factor_recovery_codes = null;
        $user->save();

        return redirect()->back()->with('success', 'User 2FA has been disabled');
    }

    /**
     * Generate a temporary password for a user and force them to change it on next login.
     * Only accessible by Super Admin.
     */
    public function resetPassword(User $user)
    {
        // Ensure the acting user is Super Admin
        if (!auth()->user()->isSuperAdmin()) {
            return response()->json([
                'status' => false,
                'message' => 'Forbidden. Only Super Admin can reset passwords.'
            ], 403);
        }

        // Cannot reset password for self (use profile settings) or other Super Admins via this method usually?
        // Requirement said "user khác" (other users).
        // Let's allow resetting other super admins if needed, but definitely not self via this flow to avoid session kill confusion?
        // Actually, preventing self-reset is good UX.
        if (auth()->id() === $user->id) {
             return response()->json([
                'status' => false,
                'message' => 'You cannot reset your own password via this tool. Use Profile Settings.'
            ], 403);
        }

        $tempPassword = \Illuminate\Support\Str::random(12);

        $user->password = \Illuminate\Support\Facades\Hash::make($tempPassword);
        $user->must_change_password = true;
        // Invalidate existing sessions? Maybe not strictly required but good practice.
        // For now, just update password.
        $user->save();

        return response()->json([
            'status' => true,
            'message' => 'Temporary password generated.',
            'temp_password' => $tempPassword // Frontend will show this in a modal
        ]);
    }
}
