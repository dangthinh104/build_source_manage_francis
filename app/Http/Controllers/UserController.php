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
        if (!Gate ::allows('isAdmin')) {
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
            'role'     => 'required|in:Admin,Default',
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
            'role'  => 'required|in:Admin,Default',
        ]);

        // Update the user
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
        if ($user -> role !== 'Admin') {
            $user -> delete();
        } else {
            $data = [
                'status' => false,
                'message' => "User $user->name is admin cannot delete"
            ];
        }
        return response()->json($data);
    }
}
