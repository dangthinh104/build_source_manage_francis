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
    public function index()
    {
        $users = User ::all();
        return Inertia ::render('Users/Index', [
            'users' => $users,
            'can_manage_users' => auth()->user()->isSuperAdmin(),
        ]);
    }

    public function create()
    {

        return Inertia ::render('Users/Create');
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

        $validated = $request -> validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role'     => 'required|in:' . User::ROLE_USER . ',' . User::ROLE_ADMIN . ',' . User::ROLE_SUPER_ADMIN,
        ]);

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

        // Validate incoming data
        $request->validate([
            'name'  => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $targetUser->id,
            'role'  => 'required|in:' . User::ROLE_USER . ',' . User::ROLE_ADMIN . ',' . User::ROLE_SUPER_ADMIN,
        ]);

        // Rank-based constraints
        if ($currentUser->isAdmin() && !$currentUser->isSuperAdmin()) {
            // Admin users have restrictions
            
            // Check 1: Cannot edit Admin or Super Admin users
            if ($targetUser->hasAdminPrivileges()) {
                return redirect()->route('users.index')->with('error', 'You cannot edit an Admin or Super Admin user.');
            }

            // Check 2: Cannot promote users to Admin or Super Admin
            if (in_array($request->input('role'), [User::ROLE_ADMIN, User::ROLE_SUPER_ADMIN])) {
                return redirect()->route('users.index')->with('error', 'You cannot promote a user to Admin level.');
            }
        }

        // Super Admin: No restrictions
        $targetUser->update($request->only('name', 'email', 'role'));

        return redirect()->route('users.index')->with('success', 'User updated successfully');
    }

    public function destroy($id) {
        $targetUser = User::findOrFail($id);
        $currentUser = auth()->user();

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

}
