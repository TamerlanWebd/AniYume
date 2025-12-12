<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AnimeController;
use App\Http\Controllers\Api\V1\TagController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\Admin\AnimeController as AdminAnimeController;
use App\Http\Controllers\Api\V1\Admin\ImportController;

Route::prefix('v1')->group(function () {
    
    Route::post('auth/login', [AuthController::class, 'login']);
    
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('auth/me', [AuthController::class, 'me']);
    });

    Route::get('anime', [AnimeController::class, 'index']);
    Route::get('anime/{slug}', [AnimeController::class, 'show']);
    
    Route::get('tags', [TagController::class, 'index']);

    Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
        Route::apiResource('anime', AdminAnimeController::class);
        
        Route::post('import/run', [ImportController::class, 'run']);
        Route::get('import/logs', [ImportController::class, 'logs']);
        Route::get('import/status/{id}', [ImportController::class, 'status']);
    });
});
