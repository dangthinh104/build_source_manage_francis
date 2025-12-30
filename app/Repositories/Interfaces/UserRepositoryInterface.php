<?php

declare(strict_types=1);

namespace App\Repositories\Interfaces;

use App\Models\User;

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
}
