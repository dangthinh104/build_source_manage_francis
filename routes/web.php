<?php

use App\Http\Controllers\ApiTokenController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentationController;
use App\Http\Controllers\ForcePasswordChangeController;
use App\Http\Controllers\QueueManagerController;
use App\Http\Controllers\UserPreferenceController;
use App\Http\Controllers\RbacController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider, and all of them will
| be assigned to the "web" middleware group.
|
*/

// Homepage - redirect based on authentication
Route::get('/', function () {
    if (\Illuminate\Support\Facades\Auth::check()) {
        $user = \Illuminate\Support\Facades\Auth::user();
        // Redirect based on role
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
| Authenticated Routes (All Users)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Include feature-specific route files
    require __DIR__.'/features/profile.php';

    // API Token Management
    Route::prefix('api-tokens')->name('api-tokens.')->group(function () {
        Route::get('/', [ApiTokenController::class, 'index'])->name('index');
        Route::post('/', [ApiTokenController::class, 'store'])->name('store');
        Route::delete('/{id}', [ApiTokenController::class, 'destroy'])->name('destroy');
    });

    // User Preferences
    Route::post('/preferences', [UserPreferenceController::class, 'update'])->name('preferences.update');

    // Include feature-specific route files
    require __DIR__.'/features/logs.php';

    // Documentation
    Route::get('/docs', [DocumentationController::class, 'index'])
        ->middleware(['verified'])
        ->name('docs.index');

    // Force Password Change
    Route::get('/auth/password/change', [ForcePasswordChangeController::class, 'show'])->name('password.change');
    Route::post('/auth/password/change', [ForcePasswordChangeController::class, 'update'])->name('password.change.store');

    /*
    |--------------------------------------------------------------------------
    | Feature Routes (Modular)
    |--------------------------------------------------------------------------
    */

    // Include feature-specific route files
    require __DIR__.'/features/users.php';
    require __DIR__.'/features/mysites.php';
    require __DIR__.'/features/build_groups.php';
    require __DIR__.'/features/env_variables.php';
    require __DIR__.'/features/parameters.php';

    /*
    |--------------------------------------------------------------------------
    | Admin Routes (Additional)
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:admin')->group(function () {
        // Queue Manager
        Route::prefix('queues')->name('queues.')->group(function () {
            Route::get('/', [QueueManagerController::class, 'index'])->name('index');
            Route::post('/retry/{uuid}', [QueueManagerController::class, 'retry'])->name('retry');
            Route::delete('/destroy/{uuid}', [QueueManagerController::class, 'destroy'])->name('destroy');
            Route::post('/retry-all', [QueueManagerController::class, 'retryAll'])->name('retry-all');
            Route::post('/flush', [QueueManagerController::class, 'flush'])->name('flush');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Super Admin Routes
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:super_admin')->group(function () {
        // RBAC Matrix
        Route::get('/rbac/matrix', [RbacController::class, 'index'])->name('rbac.matrix');
    });
});

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/
require __DIR__.'/auth.php';

/*
|--------------------------------------------------------------------------
| Fallback Route (404)
|--------------------------------------------------------------------------
*/
Route::fallback(function () {
    return Inertia::render('Errors/NotFound')->toResponse(request())->setStatusCode(404);
});
