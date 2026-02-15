<?php

use App\Http\Controllers\Api\DashboardApiController;
use App\Http\Controllers\Api\AuthApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/dashboard/content', [DashboardApiController::class, 'content'])->name('api.dashboard.content');
Route::get('/dashboard', [DashboardApiController::class, 'content'])->name('api.dashboard.index');
Route::post('/dashboard/import', [DashboardApiController::class, 'import'])->name('api.dashboard.import');
Route::post('/dashboard/import/{batchId}/chunk', [DashboardApiController::class, 'importChunk'])->name('api.dashboard.import.chunk');
Route::post('/dashboard/import/{batchId}/finalize', [DashboardApiController::class, 'importFinalize'])->name('api.dashboard.import.finalize');
Route::get('/dashboard/import/{batchId}/status', [DashboardApiController::class, 'importStatus'])->name('api.dashboard.import.status');

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Auth API
Route::post('/auth/login', [AuthApiController::class, 'login'])->name('api.auth.login');
Route::post('/auth/register', [AuthApiController::class, 'register'])->name('api.auth.register');
Route::middleware('auth:sanctum')->post('/auth/logout', [AuthApiController::class, 'logout'])->name('api.auth.logout');
