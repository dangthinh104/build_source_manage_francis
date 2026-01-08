<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Profile Routes
|--------------------------------------------------------------------------
|
| All routes related to user profile management, including basic info,
| account deletion, and two-factor authentication.
|
*/

Route::middleware('auth')->prefix('profile')->name('profile.')->group(function () {
    // Profile management
    Route::get('/edit', [ProfileController::class, 'edit'])->name('edit');
    Route::patch('/', [ProfileController::class, 'update'])->name('update');
    Route::delete('/', [ProfileController::class, 'destroy'])->name('destroy');
    
    // Two-Factor Authentication
    Route::post('/two-factor', [ProfileController::class, 'enableTwoFactor'])->name('two-factor.enable');
    Route::post('/two-factor/confirm', [ProfileController::class, 'confirmTwoFactor'])->name('two-factor.confirm');
    Route::delete('/two-factor', [ProfileController::class, 'disableTwoFactor'])->name('two-factor.disable');
});
