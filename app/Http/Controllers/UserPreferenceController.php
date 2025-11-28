<?php

namespace App\Http\Controllers;

use App\Models\UserPreference;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserPreferenceController extends Controller
{
    public function update(Request $request)
    {
        $validated = $request->validate([
            'theme_color' => 'required|in:indigo,purple,blue,emerald,rose,orange',
            'content_width' => 'required|in:default,wide,full',
            'sidebar_style' => 'required|in:gradient,solid,glass',
            'compact_mode' => 'boolean',
        ]);

        try {
            $preference = UserPreference::updateOrCreate(
                ['user_id' => Auth::id()],
                $validated
            );

            return back()->with('success', 'Preferences saved successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to save preferences: ' . $e->getMessage());
        }
    }
}
