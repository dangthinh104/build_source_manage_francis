<?php

use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| User Management Routes
|--------------------------------------------------------------------------
|
| All routes related to user management (CRUD, 2FA, password reset)
|
*/

Route::middleware(['auth', 'role:admin'])->prefix('users')->name('users.')->group(function () {
    // List and Create
    Route::get('/', [UserController::class, 'index'])->name('index');
    Route::get('/create', [UserController::class, 'create'])->name('create');
    Route::post('/', [UserController::class, 'store'])->name('store');
    
    // Edit and Update
    Route::get('/{id}/edit', [UserController::class, 'edit'])->name('edit');
    Route::put('/{id}', [UserController::class, 'update'])->name('update');
    
    // Delete (POST for compatibility with frontend)
    Route::post('/{id}/delete', [UserController::class, 'destroy'])->name('destroy');
    
    // 2FA Management (Super Admin only in controller)
    Route::post('/{id}/toggle-two-factor', [UserController::class, 'toggleTwoFactor'])->name('toggle_two_factor');
    Route::post('/{id}/reset-2fa', [UserController::class, 'resetTwoFactor'])->name('reset_2fa');
    
    // Password Reset (Super Admin only in controller)
    Route::post('/{id}/reset-password', [UserController::class, 'resetPassword'])->name('reset-password');
});
