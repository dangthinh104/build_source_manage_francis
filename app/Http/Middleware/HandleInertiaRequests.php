<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): string|null
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        // Load preference relationship if user exists
        if ($user) {
            $user->load('preference');
        }

        $sharedPreferences = $user && $user->preference
            ? $user->preference
            : (object) \App\Models\UserPreference::getDefaults();

        // Generate permission array for frontend
        $permissions = [];
        if ($user) {
            $permissionList = [
                'view_dashboard', 'view_profile', 'edit_profile', 'view_parameters',
                'manage_parameters', 'view_mysites', 'manage_mysites', 'build_mysites',
                'view_env_variables', 'manage_env_variables', 'view_logs',
                'view_users', 'manage_users',
            ];

            foreach ($permissionList as $permission) {
                $permissions[$permission] = Gate::allows($permission);
            }
        }

        // Attach preferences both as a top-level prop and under auth.user for compatibility
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? $user->toArray() + ['preference' => $sharedPreferences] : null,
                'can' => $permissions,
            ],
            'preferences' => $sharedPreferences,
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'warning' => fn () => $request->session()->get('warning'),
                'info' => fn () => $request->session()->get('info'),
            ],
        ];
    }
}
