<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EnvVariableController;
use App\Http\Controllers\LogPM2Controller;
use App\Http\Controllers\MySiteController;
use App\Http\Controllers\ParameterController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserPreferenceController;
use App\Http\Controllers\RbacController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\Auth\TwoFactorController;

Route::get('/', function () {
    // Fix login loop: redirect authenticated users to dashboard
    if (\Illuminate\Support\Facades\Auth::check()) {
        $user = \Illuminate\Support\Facades\Auth::user();
        // Redirect based on role - admins go to dashboard, users go to my_site
        if ($user->hasAdminPrivileges()) {
            return redirect()->route('dashboard');
        }
        return redirect()->route('my_site.index');
    }
    
    return Inertia::render('Auth/Login', [
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    // Dashboard - accessible to all authenticated users
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // My Sites Management - accessible to all authenticated users
    Route::get('/my-sites', [MySiteController::class, 'index'])->name('my_site.index');
    Route::get('/my-sites/{id}', [MySiteController::class, 'show'])->name('my_site.show');
    Route::post('/my-site-store', [MySiteController::class, 'store'])->name('my_site.store');
    Route::post('/my-site-update', [MySiteController::class, 'update'])->name('my_site.update');
    Route::post('/my-site-build', [MySiteController::class, 'buildMySite'])->name('my_site.build_my_site');
    Route::post('/my-site-build-status', [MySiteController::class, 'getBuildStatus'])->name('my_site.build_status');
    Route::post('/my-site-delete', [MySiteController::class, 'deleteSite'])->name('my_site.delete');
    Route::post('/my-site-log-details', [MySiteController::class, 'getLogLastBuildByID'])->name('my_site.get_content_log');
    Route::post('/my-site-open-detail', [MySiteController::class, 'getAllDetailSiteByID'])->name('my_site.open_popup_detail');
    Route::post('/my-site-history', [MySiteController::class, 'getBuildHistoryBySite'])->name('my_site.history');
    Route::post('/my-site-logs', [MySiteController::class, 'getSiteLogs'])->name('my_site.logs');
    Route::post('/my-site-view-log', [MySiteController::class, 'viewLogFile'])->name('my_site.view_log');

    // Profile Management - accessible to all authenticated users
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::post('/profile/2fa/enable', [ProfileController::class, 'enableTwoFactor'])->name('profile.2fa.enable');
    Route::post('/profile/2fa/confirm', [ProfileController::class, 'confirmTwoFactor'])->name('profile.2fa.confirm');
    Route::delete('/profile/2fa', [ProfileController::class, 'disableTwoFactor'])->name('profile.2fa.disable');

    // API Token Management
    Route::get('/api-tokens', [\App\Http\Controllers\ApiTokenController::class, 'index'])->name('api-tokens.index');
    Route::post('/api-tokens', [\App\Http\Controllers\ApiTokenController::class, 'store'])->name('api-tokens.store');
    Route::delete('/api-tokens/{id}', [\App\Http\Controllers\ApiTokenController::class, 'destroy'])->name('api-tokens.destroy');

    // User Preferences - accessible to all authenticated users
    Route::post('/preferences', [UserPreferenceController::class, 'update'])->name('preferences.update');

    // Log Viewing - accessible to all authenticated users
    Route::prefix('logs')->name('logs.')->group(function () {
        // Advance viewer (CloudWatch-style with parsed logs)
        Route::get('/advance/{subfolder}/{filename}', [LogPM2Controller::class, 'advance'])->name('advance');
        // Raw viewer (legacy - shows file content as-is)
        Route::get('/raw/{subfolder}/{filename}', [LogPM2Controller::class, 'raw'])->name('raw');
        // Legacy view route - redirects to advance
        Route::get('/view/{subfolder}/{filename}', [LogPM2Controller::class, 'view'])->name('view');
        // Download
        Route::get('/download/{subfolder}/{filename}', [LogPM2Controller::class, 'download'])->name('download');
        // Index (folder listing)
        Route::get('/{subfolder?}', [LogPM2Controller::class, 'index'])->name('index');
    });

    /*
    |--------------------------------------------------------------------------
    | Admin Routes (Admin + Super Admin)
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:admin')->group(function () {
        // User Management
        Route::resource('users', UserController::class);
        Route::get('/users/{id}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{id}', [UserController::class, 'update'])->name('users.update');
        Route::put('password', [PasswordController::class, 'update'])->name('password.update');

    // Documentation Route
    Route::get('/docs', [\App\Http\Controllers\DocumentationController::class, 'index'])
        ->middleware(['auth', 'verified'])
        ->name('docs.index');

    // Force Password Change Routes
    Route::get('/auth/password/change', [\App\Http\Controllers\ForcePasswordChangeController::class, 'show'])->name('password.change');
    Route::post('/auth/password/change', [\App\Http\Controllers\ForcePasswordChangeController::class, 'update']);

    // Admin Reset Password Route
    Route::post('/users/{user}/reset-password', [\App\Http\Controllers\UserController::class, 'resetPassword'])->name('users.reset-password');

        Route::post('/users/{id}', [UserController::class, 'destroy'])->name('users.destroy');
        Route::post('/users/{id}/toggle-two-factor', [UserController::class, 'toggleTwoFactor'])->name('users.toggle_two_factor');
        Route::post('/users/{user}/reset-2fa', [UserController::class, 'resetTwoFactor'])->name('users.reset_2fa');

        // Environment Variables
        Route::get('/envVariables', [EnvVariableController::class, 'index'])->name('envVariables.index');
        Route::post('/envVariables', [EnvVariableController::class, 'store'])->name('envVariables.store');
        Route::put('/envVariables/{id}', [EnvVariableController::class, 'update'])->name('envVariables.update');
        Route::delete('/envVariables/{id}', [EnvVariableController::class, 'destroy'])->name('envVariables.destroy');

        // Queue Manager
        Route::prefix('queues')->name('queues.')->group(function () {
            Route::get('/', [\App\Http\Controllers\QueueManagerController::class, 'index'])->name('index');
            Route::post('/retry/{uuid}', [\App\Http\Controllers\QueueManagerController::class, 'retry'])->name('retry');
            Route::delete('/destroy/{uuid}', [\App\Http\Controllers\QueueManagerController::class, 'destroy'])->name('destroy');
            Route::post('/retry-all', [\App\Http\Controllers\QueueManagerController::class, 'retryAll'])->name('retry-all');
            Route::post('/flush', [\App\Http\Controllers\QueueManagerController::class, 'flush'])->name('flush');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Super Admin Routes (Super Admin only)
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:super_admin')->group(function () {
        // Parameters
        Route::resource('parameters', ParameterController::class)->only(['index', 'store', 'update', 'destroy']);
        
        // RBAC Matrix
        Route::get('/rbac/matrix', [RbacController::class, 'index'])->name('rbac.matrix');
    });
});

// Public API for outbound build triggers
Route::get('/build-my-site-out/site/{siteName}', [MySiteController::class, 'buildMySiteOutbound']);

require __DIR__.'/auth.php';

// Fallback route for undefined paths — return a 404 Inertia page
Route::fallback(function () {
    return Inertia::render('Errors/NotFound')->toResponse(request())->setStatusCode(404);
});

