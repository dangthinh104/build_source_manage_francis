<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EnvVariableController;
use App\Http\Controllers\LogPM2Controller;
use App\Http\Controllers\MySiteController;
use App\Http\Controllers\ParameterController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserPreferenceController;
use App\Http\Middleware\RoleMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\Auth\TwoFactorController;

Route::get('/', function () {
    return Inertia::render('Auth/Login', [
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::middleware('auth')->group(function () {
    Route::post('/my-site-store', [MySiteController::class, 'store'])->name('my_site.store');
    Route::post('/my-site-log-details', [MySiteController::class, 'getLogLastBuildByID'])->name('my_site.get_content_log');
    Route::post('/my-site-open-detail', [MySiteController::class, 'getAllDetailSiteByID'])->name('my_site.open_popup_detail');
    Route::post('/my-site-history', [MySiteController::class, 'getBuildHistoryBySite'])->name('my_site.history');
    Route::post('/my-site-logs', [MySiteController::class, 'getSiteLogs'])->name('my_site.logs');
    Route::post('/my-site-view-log', [MySiteController::class, 'viewLogFile'])->name('my_site.view_log');
    Route::post('/my-site-update', [MySiteController::class, 'update'])->name('my_site.update');
    Route::post('/my-site-build', [MySiteController::class, 'buildMySite'])->name('my_site.build_my_site');
    Route::post('/my-site-delete', [MySiteController::class, 'deleteSite'])->name('my_site.delete');
    Route::resource('users', UserController::class);
    Route::get('/users/{id}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{id}', [UserController::class, 'update'])->name('users.update');
    Route::post('/users/{id}', [UserController::class, 'destroy'])->name('users.destroy');
    Route::post('/users/{id}/toggle-two-factor', [UserController::class, 'toggleTwoFactor'])->name('users.toggle_two_factor');
    // Admin-only route to force-reset a user's 2FA
    Route::post('/users/{user}/reset-2fa', [UserController::class, 'resetTwoFactor'])
        ->name('users.reset_2fa')
        ->middleware(\App\Http\Middleware\RoleMiddleware::class . ':admin');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/my-sites', [MySiteController::class, 'index'])->name('my_site.index');

    Route::get('/envVariables', [EnvVariableController::class, 'index'])->name('envVariables.index');
    Route::post('/envVariables', [EnvVariableController::class, 'store'])->name('envVariables.store');
    Route::get('/envVariables/{id}/edit', [EnvVariableController::class, 'edit'])->name('envVariables.edit');
    Route::put('/envVariables/{id}', [EnvVariableController::class, 'update'])->name('envVariables.update');
    Route::delete('/envVariables/{id}', [EnvVariableController::class, 'destroy'])->name('envVariables.destroy');
    // Parameters only accessible by super_admin
    Route::resource('parameters', ParameterController::class)->only(['index', 'store', 'update', 'destroy'])->middleware(\App\Http\Middleware\RoleMiddleware::class . ':super_admin');

});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    // Profile 2FA setup/confirm/disable routes
    Route::post('/profile/2fa/enable', [ProfileController::class, 'enableTwoFactor'])->name('profile.2fa.enable');
    Route::post('/profile/2fa/confirm', [ProfileController::class, 'confirmTwoFactor'])->name('profile.2fa.confirm');
    Route::delete('/profile/2fa', [ProfileController::class, 'disableTwoFactor'])->name('profile.2fa.disable');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::post('/preferences', [UserPreferenceController::class, 'update'])->name('preferences.update');

    Route::get('/logs/{subfolder?}', [LogPM2Controller::class, 'index'])->name('logs.index');
    Route::get('/logs/view/{subfolder}/{filename}', [LogPM2Controller::class, 'view'])->name('logs.view');
    Route::get('/logs/download/{subfolder}/{filename}', [LogPM2Controller::class, 'download'])->name('logs.download');
});
Route::get('/build-my-site-out/site/{siteName}', [MySiteController::class, 'buildMySiteOutbound']);

require __DIR__.'/auth.php';

// Fallback route for undefined paths — return a 404 Inertia page
Route::fallback(function () {
    return Inertia::render('Errors/NotFound')->toResponse(request())->setStatusCode(404);
});
