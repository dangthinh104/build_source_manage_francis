<?php

use App\Http\Controllers\BuildGroupController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Build Groups Routes
|--------------------------------------------------------------------------
|
| All routes related to build group management
|
*/

Route::middleware(['auth', 'role:admin'])->prefix('build-groups')->name('build_groups.')->group(function () {
    // CRUD
    Route::get('/', [BuildGroupController::class, 'index'])->name('index');
    Route::post('/', [BuildGroupController::class, 'store'])->name('store');
    Route::put('/{id}', [BuildGroupController::class, 'update'])->name('update');
    Route::delete('/{id}', [BuildGroupController::class, 'destroy'])->name('destroy');
    
    // Actions
    Route::post('/{id}/build', [BuildGroupController::class, 'build'])->name('build');
});
