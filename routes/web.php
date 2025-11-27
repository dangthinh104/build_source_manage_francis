<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EnvVariableController;
use App\Http\Controllers\LogPM2Controller;
use App\Http\Controllers\MySiteController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\RoleAdminMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

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
    Route::post('/my-site-update', [MySiteController::class, 'update'])->name('my_site.update');
    Route::post('/my-site-build', [MySiteController::class, 'buildMySite'])->name('my_site.build_my_site');
    Route::resource('users', UserController::class);
    Route::get('/users/{id}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{id}', [UserController::class, 'update'])->name('users.update');
    Route::post('/users/{id}', [UserController::class, 'destroy'])->name('users.destroy');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/envVariables', [EnvVariableController::class, 'index'])->name('envVariables.index');
    Route::post('/envVariables', [EnvVariableController::class, 'store'])->name('envVariables.store');
    Route::get('/envVariables/{id}/edit', [EnvVariableController::class, 'edit'])->name('envVariables.edit');
    Route::put('/envVariables/{id}', [EnvVariableController::class, 'update'])->name('envVariables.update');
    Route::delete('/envVariables/{id}', [EnvVariableController::class, 'destroy'])->name('envVariables.destroy');
//    Route::resource('envVariables', EnvVariableController::class);

})->middleware(RoleAdminMiddleware::class);;

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/logs/{subfolder?}', [LogPM2Controller::class, 'index'])->name('logs.index');
    Route::get('/logs/view/{subfolder}/{filename}', [LogPM2Controller::class, 'view'])->name('logs.view');
    Route::get('/logs/download/{subfolder}/{filename}', [LogPM2Controller::class, 'download'])->name('logs.download');
});
Route::get('/build-my-site-out/site/{siteName}', [MySiteController::class, 'buildMySiteOutbound']);

require __DIR__.'/auth.php';
