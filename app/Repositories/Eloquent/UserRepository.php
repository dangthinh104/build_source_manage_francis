<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Repositories\Interfaces\UserRepositoryInterface;

/**
 * User Repository
 * 
 * Handles all User database operations.
 */
class UserRepository extends BaseRepository implements UserRepositoryInterface
{
    /**
     * Specify Model class name
     *
     * @return string
     */
    protected function model(): string
    {
        return User::class;
    }

    /**
     * Find user by name
     *
     * @param string $name
     * @return User|null
     */
    public function findByName(string $name): ?User
    {
        return $this->newQuery()
            ->where('name', $name)
            ->first();
    }

    /**
     * Find user by email
     *
     * @param string $email
     * @return User|null
     */
    public function findByEmail(string $email): ?User
    {
        return $this->newQuery()
            ->where('email', $email)
            ->first();
    }
}
