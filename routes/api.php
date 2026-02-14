<?php

use App\Http\Controllers\Api\DashboardApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/dashboard/content', [DashboardApiController::class, 'content'])->name('api.dashboard.content');
Route::post('/dashboard/import', [DashboardApiController::class, 'import'])->name('api.dashboard.import');
Route::post('/dashboard/import/{batchId}/chunk', [DashboardApiController::class, 'importChunk'])->name('api.dashboard.import.chunk');
Route::post('/dashboard/import/{batchId}/finalize', [DashboardApiController::class, 'importFinalize'])->name('api.dashboard.import.finalize');
Route::get('/dashboard/import/{batchId}/status', [DashboardApiController::class, 'importStatus'])->name('api.dashboard.import.status');

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
