<?php

declare(strict_types=1);

namespace App\Repositories\Interfaces;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * User Repository Interface
 * 
 * Defines contract for User data access operations.
 */
interface UserRepositoryInterface extends RepositoryInterface
{
    /**
     * Find user by name
     *
     * @param string $name
     * @return User|null
     */
    public function findByName(string $name): ?User;

    /**
     * Find user by email
     *
     * @param string $email
     * @return User|null
     */
    public function findByEmail(string $email): ?User;

    /**
     * Get paginated users with optional filters
     *
     * @param string|null $nameFilter Filter by name
     * @param string|null $emailFilter Filter by email
     * @param int $perPage Items per page
     * @return LengthAwarePaginator
     */
    public function getPaginatedWithFilters(
        ?string $nameFilter = null,
        ?string $emailFilter = null,
        int $perPage = 10
    ): LengthAwarePaginator;

    /**
     * Check if email exists (for uniqueness validation)
     *
     * @param string $email Email to check
     * @param int|null $excludeId Exclude this ID from check (for updates)
     * @return bool
     */
    public function emailExists(string $email, ?int $excludeId = null): bool;

    /**
     * Toggle two-factor authentication for a user
     *
     * @param int $userId User ID
     * @param bool $enable True to enable, false to disable
     * @return bool
     */
    public function toggle2FA(int $userId, bool $enable): bool;

    /**
     * Reset (disable) two-factor authentication for a user
     *
     * @param int $userId User ID
     * @return bool
     */
    public function reset2FA(int $userId): bool;

    /**
     * Set a temporary password for a user and mark for password change
     *
     * @param int $userId User ID
     * @param string $password Hashed password
     * @return bool
     */
    public function setTemporaryPassword(int $userId, string $password): bool;

    /**
     * Get all users with a specific role
     *
     * @param string $role Role name
     * @return Collection<int, User>
     */
    public function getUsersByRole(string $role): Collection;

    /**
     * Update user profile (name, email)
     *
     * @param int $userId User ID
     * @param array<string, mixed> $data Profile data
     * @return bool
     */
    public function updateProfile(int $userId, array $data): bool;
}
