<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * Role constants
     */
    public const ROLE_SUPER_ADMIN = 'super_admin';
    public const ROLE_ADMIN = 'admin';
    public const ROLE_USER = 'user';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_confirmed_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function preference(): HasOne
    {
        return $this->hasOne(UserPreference::class);
    }

    public function buildHistories()
    {
        return $this->hasMany(\App\Models\BuildHistory::class, 'user_id');
    }

    /**
     * Check if user is a super admin (full god mode access).
     */
    public function isSuperAdmin(): bool
    {
        return strtolower($this->role ?? '') === self::ROLE_SUPER_ADMIN;
    }

    /**
     * Check if user is an admin (can build sites and manage env variables).
     * Note: Super admin is NOT included as admin for strict RBAC.
     */
    public function isAdmin(): bool
    {
        return strtolower($this->role ?? '') === self::ROLE_ADMIN;
    }

    /**
     * Check if user is a regular user (view-only access).
     */
    public function isUser(): bool
    {
        return strtolower($this->role ?? '') === self::ROLE_USER;
    }

    /**
     * Check if user has admin privileges (admin OR super_admin).
     * Use this when checking if user can perform admin-level actions.
     */
    public function hasAdminPrivileges(): bool
    {
        return $this->isAdmin() || $this->isSuperAdmin();
    }

    /**
     * Check if the user has enabled two-factor authentication (confirmed).
     *
     * @return bool
     */
    public function hasEnabledTwoFactor(): bool
    {
        return $this->two_factor_confirmed_at !== null;
    }

    /**
     * Determine if the user should be redirected to the two-factor setup flow.
     * Returns true when 2FA secret exists but not yet confirmed.
     */
    public function shouldRedirectToTwoFactorSetup(): bool
    {
        return $this->two_factor_secret !== null && $this->two_factor_confirmed_at === null;
    }

    /**
     * Determine if the user should be redirected to the two-factor challenge flow.
     * Returns true when 2FA is enabled and already confirmed.
     */
    public function shouldRedirectToTwoFactorChallenge(): bool
    {
        return $this->two_factor_secret !== null && $this->two_factor_confirmed_at !== null;
    }

    /**
     * Check if user has a specific permission based on role_permissions table.
     */
    public function hasPermission(string $permission): bool
    {
        return RolePermission::hasPermission($this->role, $permission);
    }

    /**
     * Get all permissions for the current user.
     */
    public function getPermissions(): array
    {
        return RolePermission::getPermissionsForRole($this->role);
    }
}
