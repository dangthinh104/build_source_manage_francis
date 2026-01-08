<?php

use App\Http\Controllers\ParameterController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Parameters Routes
|--------------------------------------------------------------------------
|
| All routes related to application parameter management (Super Admin only)
|
*/

Route::middleware(['auth', 'role:super_admin'])->prefix('parameters')->name('parameters.')->group(function () {
    // CRUD
    Route::get('/', [ParameterController::class, 'index'])->name('index');
    Route::post('/', [ParameterController::class, 'store'])->name('store');
    Route::put('/{id}', [ParameterController::class, 'update'])->name('update');
    Route::delete('/{id}', [ParameterController::class, 'destroy'])->name('destroy');
});
