<?php

use App\Http\Controllers\SendAlertController;
use Illuminate\Support\Facades\Route;


Route::post('/receive-sms', [SendAlertController::class, 'sendSMSTelegram']);
Route::middleware(['auth:sanctum', 'throttle:10,1'])->post('/sites/{id}/build', [\App\Http\Controllers\Api\SiteBuildApiController::class, 'triggerBuild']);

// Fallback for API routes
Route::fallback(function () {
    return response()->json([
        'message' => 'API route not found.',
        'status' => 'error'
    ], 404);
});
