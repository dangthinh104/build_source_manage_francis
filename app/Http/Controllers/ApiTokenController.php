<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\User;

class ApiTokenController extends Controller
{
    /**
     * List active tokens for the user.
     */
    public function index(Request $request): JsonResponse
    {
        $tokens = $request->user()->tokens->map(function ($token) {
            return [
                'id' => $token->id,
                'name' => $token->name,
                'last_used_at' => $token->last_used_at?->diffForHumans() ?? 'Never',
                'expires_at' => $token->expires_at ? $token->expires_at->format('Y-m-d H:i') : 'Never',
                'created_at' => $token->created_at->format('Y-m-d'),
            ];
        });

        return response()->json(['tokens' => $tokens]);
    }

    /**
     * Create a new API token.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'expires_days' => 'nullable|integer|min:1|max:365',
        ]);

        $user = $request->user();
        
        // Permission Check: only Admin/SuperAdmin? 
        // Prompt said "Integration... Only visible if user.role === 'admin' || 'super_admin'".
        // So we should enforce it here too.
        if (!$user->hasAdminPrivileges()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        if ($request->has('expires_days')) {
            $days = $request->input('expires_days');
            $expiresAt = $days ? now()->addDays((int)$days) : null;
        } else {
            $expiresAt = now()->addDays(90);
        }

        $token = $user->createToken($request->name, ['build'], $expiresAt);

        return response()->json([
            'token' => $token->plainTextToken,
            'message' => 'Token created successfully. Copy it now, it will not be shown again.'
        ]);
    }

    /**
     * Revoke a token.
     */
    public function destroy(Request $request, string $tokenId): JsonResponse
    {
        $request->user()->tokens()->where('id', $tokenId)->delete();

        return response()->json(['message' => 'Token revoked.']);
    }
}
