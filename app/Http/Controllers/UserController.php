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
        return Inertia ::render('Users/Index', ['users' => $users]);
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

        User ::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => bcrypt($validated['password']),
            'role'     => $validated['role'],
        ]);

        return redirect() -> route('users.index') -> with('success', 'User added successfully!');
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

        $data = [
          'status' => true,
          'message' => 'User deleted successfully'
        ];

        $user = User ::findOrFail($id);
        // Only super_admin can delete users
        if (!auth()->user()->isSuperAdmin()) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 403);
        }

        $user->delete();
        return response()->json($data);
    }
}
