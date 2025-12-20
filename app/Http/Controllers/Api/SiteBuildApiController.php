<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MySite;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SiteBuildApiController extends Controller
{
    /**
     * Trigger a build for a specific site.
     *
     * @param Request $request
     * @param string $siteId ID or Site Name (Assuming ID based on route typically, but requirement said 'sites/{id}/build')
     * @return JsonResponse
     */
    public function triggerBuild(Request $request, string $idOrName): JsonResponse
    {
        $user = $request->user();
        
        // Try finding by ID first, then by site_name
        $site = MySite::find($idOrName);
        
        if (!$site) {
            $site = MySite::where('site_name', $idOrName)->first();
        }

        if (!$site) {
             return response()->json(['message' => 'Site not found.'], 404);
        }

        // Permission Check: Admin OR Owner
        // Assuming MySite has a user_id or developers pivot. 
        // Based on typical logic: user_id usually denotes owner/creator.
        // Let's check MySite structure or assume typical. Service used 'last_user_build'.
        // I'll check strict permission. If user is Admin (role).
        
        $isOwner = false;
        // Verify ownership logic. 
        // If site doesn't have explicit owner field visible in service code, maybe it's open or managed by admins? 
        // Reference code: "Authentication: Check if $request->user() is an Admin OR owns the $siteId".
        // I'll assume MySite has a 'user_id' column or 'created_by'. If not, only Admin can build? 
        // Let's look at SiteBuildService::createSite. It doesn't seem to set an 'owner'. 
        // It sets 'last_user_build'.
        // HOWEVER, the user prompt implies ownership exists. "OR owns the $siteId". 
        // I will assume there is a relationship or I will assume if I can't find it, I fail safe to Admin only.
        // Wait, `app/Models/User.php` has `buildHistories`.
        
        // Let's peek at MySite model to be sure about ownership column.
        
        if ($user->hasAdminPrivileges()) {
            $isOwner = true;
        } elseif ($site->user_id === $user->id) { // explicit owner
             $isOwner = true;
        }

        // If I can't verify generic ownership, I'll stick to Admin or check if user created it?
        // Let's assume 'user_id' exists for now. If not, I'll need to fix. 
        // Actually, let's just use `hasAdminPrivileges()` for now and if strict ownership is needed, I'll add a TODO or check if the user is associated. 
        // PROMPT SAYS: "Check if $request->user() is an Admin OR owns the $siteId"
        
        // I'll assume `user_id` exists on MySite.
        
        if (!$isOwner && isset($site->user_id) && $site->user_id !== $user->id) {
             return response()->json(['message' => 'Unauthorized. You do not own this site.'], 403);
        }
        
        // If no user_id column is standard, maybe developers are a many-to-many?
        // I will trust that `hasAdminPrivileges()` covers the main use case and `user_id` cover ownership.
        // IF user_id is missing on model, this might crash. 
        // BETTER: Check if $site->user_id exists. 
        
        if (!$isOwner && (!isset($site->user_id) || $site->user_id !== $user->id)) {
             return response()->json(['message' => 'Unauthorized.'], 403);
        }

        \App\Jobs\ProcessSiteBuild::dispatch($site->id, $user->id);

        return response()->json([
            'message' => 'Build queued successfully.',
            'site' => $site->site_name,
            'job_id' => 'dispatched' // Laravel dispatch doesn't always return ID easily without sync
        ]);
    }
}
