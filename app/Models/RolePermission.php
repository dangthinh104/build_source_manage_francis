<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class RolePermission extends Model
{
    protected $fillable = ['role', 'permission'];

    /**
     * Get all permissions for a specific role.
     * Uses cache for performance.
     */
    public static function getPermissionsForRole(string $role): array
    {
        return Cache::remember("permissions_{$role}", 3600, function () use ($role) {
            // Check if role has wildcard permission
            $hasWildcard = self::where('role', $role)
                ->where('permission', '*')
                ->exists();

            if ($hasWildcard) {
                return ['*']; // Super admin has all permissions
            }

            return self::where('role', $role)
                ->pluck('permission')
                ->toArray();
        });
    }

    /**
     * Check if a role has a specific permission.
     */
    public static function hasPermission(string $role, string $permission): bool
    {
        $permissions = self::getPermissionsForRole($role);

        // Check for wildcard (super_admin)
        if (in_array('*', $permissions)) {
            return true;
        }

        return in_array($permission, $permissions);
    }

    /**
     * Clear permissions cache.
     */
    public static function clearCache(): void
    {
        Cache::forget('permissions_user');
        Cache::forget('permissions_admin');
        Cache::forget('permissions_super_admin');
    }
}
