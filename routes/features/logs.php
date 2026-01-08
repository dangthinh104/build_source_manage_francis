<?php

use App\Http\Controllers\LogPM2Controller;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Logs Routes
|--------------------------------------------------------------------------
|
| All routes related to PM2 log viewing and management (read-only)
|
*/

Route::middleware(['auth', 'role:admin'])->prefix('logs')->name('logs.')->group(function () {
    // List logs
    Route::get('/', [LogPM2Controller::class, 'index'])->name('index');
    Route::get('/{subfolder}', [LogPM2Controller::class, 'index'])->name('subfolder');
    
    // View logs
    Route::get('/{subfolder}/{filename}/raw', [LogPM2Controller::class, 'raw'])->name('raw');
    Route::get('/{subfolder}/{filename}/advance', [LogPM2Controller::class, 'advance'])->name('advance');
    Route::get('/{subfolder}/{filename}/view', [LogPM2Controller::class, 'view'])->name('view'); // Legacy redirect
    
    // Download
    Route::get('/{subfolder}/{filename}/download', [LogPM2Controller::class, 'download'])->name('download');
});
