<?php

use App\Http\Controllers\Api\V1\LicenseController;
use App\Http\Controllers\Api\V1\SftpController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| License Management API - Version 1
|
| All endpoints accept POST requests with JSON body.
| No authentication required (license key is the auth mechanism).
|
*/

Route::prefix('v1')->name('api.v1.')->middleware('throttle:60,1')->group(function () {
    Route::prefix('license')->name('license.')->group(function () {
        Route::post('/activate', [LicenseController::class, 'activate'])->name('activate');
        Route::post('/validate', [LicenseController::class, 'validate'])->name('validate');
        Route::post('/deactivate', [LicenseController::class, 'deactivate'])->name('deactivate');
        Route::post('/status', [LicenseController::class, 'status'])->name('status');
    });

    Route::prefix('sftp')->name('sftp.')->group(function () {
        Route::post('/validate', [SftpController::class, 'validate'])->name('validate');
    });
});
