<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\AnimeManagementController;
use App\Http\Controllers\Admin\TagManagementController;
use App\Http\Controllers\Admin\ImportManagementController;
use App\Http\Controllers\Admin\AuditLogController;

Route::get('/', function () {
    return redirect('/admin/login');
});

Route::prefix('admin')->name('admin.')->group(function () {
    
    Route::get('login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('login', [AuthController::class, 'login'])->name('login.post');
    
    Route::middleware(['auth', 'admin'])->group(function () {
        Route::prefix('episodes')->name('episodes.')->group(function () {
            Route::get('/', [App\Http\Controllers\Admin\EpisodeManagementController::class, 'index'])->name('index');
            Route::post('/import-all', [App\Http\Controllers\Admin\EpisodeManagementController::class, 'importAll'])->name('import-all');
            Route::post('/{anime}/import', [App\Http\Controllers\Admin\EpisodeManagementController::class, 'importForAnime'])->name('import-for-anime');
            Route::delete('/{id}', [App\Http\Controllers\Admin\EpisodeManagementController::class, 'destroy'])->name('destroy');
        });
        
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');
        
        Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
        
        Route::resource('anime', AnimeManagementController::class);
        
        Route::resource('tags', TagManagementController::class)->except(['show']);
        
        Route::prefix('import')->name('import.')->group(function () {
            Route::get('/', [ImportManagementController::class, 'index'])->name('index');
            Route::post('run', [ImportManagementController::class, 'run'])->name('run');
            Route::get('logs', [ImportManagementController::class, 'logs'])->name('logs');
        });
        
        Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit-logs');
    });
});
