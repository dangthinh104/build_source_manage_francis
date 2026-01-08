<?php

use App\Http\Controllers\EnvVariableController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Environment Variables Routes
|--------------------------------------------------------------------------
|
| All routes related to environment variable management
|
*/

Route::middleware(['auth', 'role:admin'])->prefix('envVariables')->name('envVariables.')->group(function () {
    // CRUD
    Route::get('/', [EnvVariableController::class, 'index'])->name('index');
    Route::post('/', [EnvVariableController::class, 'store'])->name('store');
    Route::put('/{id}', [EnvVariableController::class, 'update'])->name('update');
    Route::delete('/{id}', [EnvVariableController::class, 'destroy'])->name('destroy');
});
