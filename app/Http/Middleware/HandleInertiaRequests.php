<?php

namespace App\Http\Middleware;

use App\Http\Resources\AuthUserResource;
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

        // Get defaults or user preferences
        $defaults = \App\Models\UserPreference::getDefaults();
        $userPref = $user?->preference;

        // Only expose necessary preference fields (no id, user_id, timestamps)
        $cleanPreferences = [
            'theme_color' => $userPref?->theme_color ?? $defaults['theme_color'],
            'content_width' => $userPref?->content_width ?? $defaults['content_width'],
            'sidebar_style' => $userPref?->sidebar_style ?? $defaults['sidebar_style'],
            'compact_mode' => $userPref?->compact_mode ?? $defaults['compact_mode'],
        ];

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

        // Build secure user data using Resource (excludes sensitive fields)
        // NOTE: preference is NOT included here - it's at root level as 'preferences'
        $secureUserData = $user
            ? (new AuthUserResource($user))->toArray($request)
            : null;

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $secureUserData,
                'can' => $permissions,
            ],
            'preferences' => $cleanPreferences,
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'warning' => fn () => $request->session()->get('warning'),
                'info' => fn () => $request->session()->get('info'),
            ],
        ];
    }
}

