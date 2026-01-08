<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\User\DeleteUserRequest;
use App\Http\Requests\User\ResetPasswordRequest;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\ToggleTwoFactorRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\User;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Response;

/**
 * User Controller
 * 
 * Handles HTTP requests for user management.
 * Delegates data operations to UserRepository.
 * Uses Form Requests for validation and authorization.
 */
class UserController extends BaseController
{
    /**
     * Constructor - Inject dependencies
     *
     * @param UserRepositoryInterface $userRepository
     */
    public function __construct(
        protected UserRepositoryInterface $userRepository
    ) {}

    /**
     * Display a paginated list of users with optional filters
     *
     * @param Request $request
     * @return Response
     */
    public function index(Request $request): Response
    {
        $users = $this->userRepository->getPaginatedWithFilters(
            $request->input('name'),
            $request->input('email'),
            10
        );

        return inertia('Users/Index', [
            'users' => $users,
            'filters' => $request->only(['name', 'email']),
            'can_manage_users' => auth()->user()->isSuperAdmin(),
        ]);
    }

    /**
     * Show the form for creating a new user
     *
     * @return Response
     */
    public function create(): Response
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

        return inertia('Users/Create', [
            'availableRoles' => $availableRoles,
        ]);
    }

    /**
     * Show the form for editing a user
     *
     * @param int $id
     * @return Response
     */
    public function edit(int $id): Response
    {
        $targetUser = $this->userRepository->findOrFail($id);
        $currentUser = auth()->user();

        // Rank-based constraints: Admin cannot edit Admin or Super Admin
        if ($currentUser->isAdmin() && !$currentUser->isSuperAdmin()) {
            if ($targetUser->hasAdminPrivileges()) {
                abort(403, 'You cannot edit an Admin or Super Admin user.');
            }
        }

        return inertia('Users/Edit', [
            'user' => $targetUser,
        ]);
    }

    /**
     * Store a newly created user
     *
     * @param StoreUserRequest $request
     * @return RedirectResponse
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        try {
            $validated = $request->validated();
            $currentUser = auth()->user();

            // SECURITY: Restrict role assignment based on current user's role
            if (!$currentUser->isSuperAdmin()) {
                if (in_array($validated['role'], [User::ROLE_ADMIN, User::ROLE_SUPER_ADMIN])) {
                    return $this->redirectWithError('users.create', 'You do not have permission to create Admin or Super Admin users.');
                }
                $validated['role'] = User::ROLE_USER;
            }

            // Generate 2FA secret by default for new users
            $secret = bin2hex(random_bytes(32));

            $this->userRepository->create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => bcrypt($validated['password']),
                'role' => $validated['role'],
                'two_factor_secret' => encrypt($secret),
            ]);

            return $this->redirectWithSuccess('users.index', 'User created successfully! 2FA is enabled by default.');
        } catch (\Exception $e) {
            return $this->redirectWithError('users.create', 'Failed to create user: ' . $e->getMessage());
        }
    }

    /**
     * Update the specified user
     *
     * @param UpdateUserRequest $request
     * @param int $id
     * @return RedirectResponse
     */
    public function update(UpdateUserRequest $request, int $id): RedirectResponse
    {
        try {
            $targetUser = $this->userRepository->findOrFail($id);
            $currentUser = auth()->user();

            // Rule B: Super Admin Protection
            if ($targetUser->isSuperAdmin()) {
                // Allow super_admin to update their own name/email (not role)
                if ($currentUser->id === $targetUser->id) {
                    $this->userRepository->updateProfile($id, $request->only('name', 'email'));
                    return $this->redirectWithSuccess('users.index', 'Profile updated successfully');
                }
                
                // Other users cannot edit super_admin accounts
                return $this->redirectWithError('users.index', 'Super Admin accounts cannot be modified via UI.');
            }

            // Rank-based constraints for non-super-admin editors
            if ($currentUser->isAdmin() && !$currentUser->isSuperAdmin()) {
                // Admin users cannot edit other admins
                if ($targetUser->hasAdminPrivileges()) {
                    return $this->redirectWithError('users.index', 'You cannot edit an Admin or Super Admin user.');
                }

                // Admin cannot change roles
                $this->userRepository->updateProfile($id, $request->only('name', 'email'));
                return $this->redirectWithSuccess('users.index', 'User updated successfully');
            }

            // Super Admin updating non-super-admin user: Full access
            $updateData = $request->only('name', 'email');
            if ($request->has('role') && $currentUser->isSuperAdmin()) {
                $updateData['role'] = $request->input('role');
            }
            
            $this->userRepository->update($id, $updateData);

            return $this->redirectWithSuccess('users.index', 'User updated successfully');
        } catch (\Exception $e) {
            return $this->redirectWithError('users.index', 'Failed to update user: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified user
     *
     * @param DeleteUserRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(DeleteUserRequest $request, int $id): JsonResponse
    {
        try {
            $targetUser = $this->userRepository->findOrFail($id);
            $currentUser = auth()->user();

            // Rule B: Cannot delete super_admin via UI
            if ($targetUser->isSuperAdmin()) {
                return $this->fail('Super Admin accounts cannot be deleted via UI.', 403);
            }

            // Prevent self-deletion
            if ($currentUser->id === $targetUser->id) {
                return $this->fail('You cannot delete your own account.', 403);
            }

            // Rank-based constraints for deletion
            if ($currentUser->isAdmin() && !$currentUser->isSuperAdmin()) {
                if ($targetUser->hasAdminPrivileges()) {
                    return $this->fail('You cannot delete an Admin or Super Admin user.', 403);
                }
            }

            $this->userRepository->delete($id);

            return $this->success(null, 'User deleted successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500, $e);
        }
    }

    /**
     * Toggle two-factor authentication for a user
     *
     * @param ToggleTwoFactorRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function toggleTwoFactor(ToggleTwoFactorRequest $request, int $id): JsonResponse
    {
        try {
            $validated = $request->validated();
            $enable = $validated['enable'];

            $this->userRepository->toggle2FA($id, $enable);

            $message = $enable 
                ? '2FA enabled for user. User must scan QR code on next login.'
                : '2FA disabled for user.';

            return $this->success(null, $message);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500, $e);
        }
    }

    /**
     * Force reset/disable two-factor authentication for a user
     *
     * @param User $user
     * @return RedirectResponse
     */
    public function resetTwoFactor(User $user): RedirectResponse
    {
        try {
            // Ensure the acting user is Super Admin
            if (!auth()->user() || !auth()->user()->isSuperAdmin()) {
                abort(403);
            }

            $this->userRepository->reset2FA($user->id);

            return $this->redirectWithSuccess('users.index', 'User 2FA has been disabled');
        } catch (\Exception $e) {
            return $this->redirectWithError('users.index', 'Failed to reset 2FA: ' . $e->getMessage());
        }
    }

    /**
     * Generate a temporary password for a user
     *
     * @param ResetPasswordRequest $request
     * @param User $user
     * @return JsonResponse
     */
    public function resetPassword(ResetPasswordRequest $request, User $user): JsonResponse
    {
        try {
            // Cannot reset own password via this tool
            if (auth()->id() === $user->id) {
                return $this->fail('You cannot reset your own password via this tool. Use Profile Settings.', 403);
            }

            $tempPassword = Str::random(12);

            $this->userRepository->setTemporaryPassword(
                $user->id,
                Hash::make($tempPassword)
            );

            return $this->success([
                'temp_password' => $tempPassword
            ], 'Temporary password generated.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500, $e);
        }
    }
}
