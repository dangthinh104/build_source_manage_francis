<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
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

        // Attach preferences both as a top-level prop and under auth.user for compatibility
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? $user->toArray() + ['preference' => $sharedPreferences] : null,
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
