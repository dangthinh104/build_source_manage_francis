<?php

namespace App\Http\Controllers;

use App\Models\RolePermission;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RbacController extends Controller
{
    /**
     * Display the RBAC permission matrix
     */
    public function index()
    {
        // Get all permissions from the database grouped by role
        $rolePermissionsData = RolePermission::all()->groupBy('role');

        // Get unique permissions list
        $allPermissions = RolePermission::select('permission')
            ->distinct()
            ->orderBy('permission')
            ->pluck('permission')
            ->toArray();

        // Build matrix data
        $roles = ['user', 'admin', 'super_admin'];
        $matrix = [];

        foreach ($roles as $role) {
            $rolePerms = $rolePermissionsData->get($role, collect())->pluck('permission')->toArray();
            
            // Check if super_admin has wildcard
            if ($role === 'super_admin' && in_array('*', $rolePerms)) {
                $matrix[$role] = ['*']; // Wildcard means full access
            } else {
                $matrix[$role] = $rolePerms;
            }
        }

        return Inertia::render('Rbac/Index', [
            'permissions' => $allPermissions,
            'matrix' => $matrix,
            'roles' => $roles,
        ]);
    }
}
