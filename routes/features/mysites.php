<?php

use App\Http\Controllers\MySiteController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| MySites Routes
|--------------------------------------------------------------------------
|
| All routes related to site management
|
*/

Route::middleware(['auth'])->prefix('my-sites')->name('my_site.')->group(function () {
    // List and Detail (Available to all authenticated users)
    Route::get('/', [MySiteController::class, 'index'])->name('index');
    Route::get('/{id}', [MySiteController::class, 'show'])->name('show');
    
    // Admin-only CRUD operations
    Route::middleware('role:admin')->group(function () {
        Route::post('/', [MySiteController::class, 'store'])->name('store');
        Route::put('/{id}', [MySiteController::class, 'update'])->name('update');
        Route::delete('/{id}', [MySiteController::class, 'destroy'])->name('destroy');
    });
});

// Legacy POST-based routes (for backward compatibility with existing frontend)
// TODO: Migrate frontend to use RESTful routes above
Route::middleware(['auth'])->group(function () {
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
});
