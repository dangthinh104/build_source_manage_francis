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
        if (!auth()->user() || !auth()->user()->isAdmin()) {
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

        $user = User ::findOrFail($id);

        return Inertia ::render('Users/Edit', [
            'user' => $user,
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
            'role'     => 'required|in:user,admin,super_admin',
        ]);

        // Generate 2FA secret by default for new users
        $secret = bin2hex(random_bytes(32));

        User ::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => bcrypt($validated['password']),
            'role'     => $validated['role'],
            'two_factor_secret' => encrypt($secret),
            'two_factor_enabled' => true,
        ]);

        return redirect() -> route('users.index') -> with('success', 'User added successfully! 2FA is enabled by default.');
    }



    public function update(Request $request, $id)
    : RedirectResponse {

        $user = User ::findOrFail($id);

        // Validate incoming data
        $request -> validate([
            'name'  => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user -> id,
            'role'  => 'required|in:user,admin,super_admin',
        ]);

        // Update the user
        // Only super_admin can change role to admin or super_admin
        if ($request->input('role') !== $user->role) {
            if (!auth()->user()->isSuperAdmin()) {
                return redirect() -> route('users.index') -> with('error', 'Only Super Admin can change roles');
            }
        }

        $user -> update($request -> only('name', 'email', 'role'));

        // Redirect or send a success message
        return redirect() -> route('users.index') -> with('success', 'User updated successfully');
    }

    public function destroy($id) {
        // Strictly ensure ONLY super admin can delete users
        if (!auth()->user()->isSuperAdmin()) {
            return response()->json([
                'status' => false,
                'message' => 'Forbidden. Only Super Admin can delete users.'
            ], 403);
        }

        $user = User ::findOrFail($id);
        $user->delete();
        
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
            $user->two_factor_enabled = true;
            $user->save();

            return response()->json([
                'status' => true,
                'message' => '2FA enabled for user. User must scan QR code on next login.',
            ]);
        } else {
            // Disable 2FA
            $user->two_factor_secret = null;
            $user->two_factor_recovery_codes = null;
            $user->two_factor_enabled = false;
            $user->save();

            return response()->json([
                'status' => true,
                'message' => '2FA disabled for user.',
            ]);
        }
    }
}
