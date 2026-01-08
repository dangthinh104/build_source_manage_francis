<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

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
     * {@inheritDoc}
     */
    public function findByName(string $name): ?User
    {
        return $this->newQuery()
            ->where('name', $name)
            ->first();
    }

    /**
     * {@inheritDoc}
     */
    public function findByEmail(string $email): ?User
    {
        return $this->newQuery()
            ->where('email', $email)
            ->first();
    }

    /**
     * {@inheritDoc}
     */
    public function getPaginatedWithFilters(
        ?string $nameFilter = null,
        ?string $emailFilter = null,
        int $perPage = 10
    ): LengthAwarePaginator {
        $query = $this->newQuery();

        if ($nameFilter) {
            $query->where('name', 'like', '%' . $nameFilter . '%');
        }

        if ($emailFilter) {
            $query->where('email', 'like', '%' . $emailFilter . '%');
        }

        return $query->latest()->paginate($perPage)->withQueryString();
    }

    /**
     * {@inheritDoc}
     */
    public function emailExists(string $email, ?int $excludeId = null): bool
    {
        $query = $this->newQuery()->where('email', $email);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * {@inheritDoc}
     */
    public function toggle2FA(int $userId, bool $enable): bool
    {
        $user = $this->findOrFail($userId);

        if ($enable) {
            // Enable 2FA - Generate a generic secret
            $secret = bin2hex(random_bytes(32));
            $user->two_factor_secret = encrypt($secret);
        } else {
            // Disable 2FA
            $user->two_factor_secret = null;
            $user->two_factor_recovery_codes = null;
            $user->two_factor_confirmed_at = null;
        }

        return $user->save();
    }

    /**
     * {@inheritDoc}
     */
    public function reset2FA(int $userId): bool
    {
        $user = $this->findOrFail($userId);

        $user->two_factor_secret = null;
        $user->two_factor_confirmed_at = null;
        $user->two_factor_recovery_codes = null;

        return $user->save();
    }

    /**
     * {@inheritDoc}
     */
    public function setTemporaryPassword(int $userId, string $password): bool
    {
        return $this->update($userId, [
            'password' => $password,
            'must_change_password' => true,
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function getUsersByRole(string $role): Collection
    {
        return $this->newQuery()
            ->where('role', $role)
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function updateProfile(int $userId, array $data): bool
    {
        return $this->update($userId, $data);
    }
}
