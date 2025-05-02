<?php

use App\Http\Controllers\Api\FileController;
use App\Http\Controllers\Api\SettingsController;
use App\Http\Controllers\Api\UploadController;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;
use Orion\Facades\Orion;

Route::group(['as' => 'api.'], function () {
    Orion::resource('files', FileController::class)
        ->except([
            'update',
            'batch',
            'batchStore',
            'batchUpdate',
            'batchRestore',
            'batchDestroy',
            'restore',
        ])->withoutMiddleware(VerifyCsrfToken::class);
    Orion::hasManyResource('files', 'uploads', UploadController::class)
        ->except([
            'search',
            'batch',
            'batchStore',
            'batchUpdate',
            'batchRestore',
            'batchDestroy',
            'store',
            'restore',
            'destroy',
            'associate',
            'dissociate',
            'attach',
            'detach',
            'sync',
            'batch',
            'toggle',
            'updatePivot',
        ]);
    Route::get('settings', [SettingsController::class, 'get'])->middleware([
        'auth:sanctum',
    ]);
})->withoutMiddleware(VerifyCsrfToken::class);
