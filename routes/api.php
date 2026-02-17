<?php

use App\Http\Controllers\Api\DashboardApiController;
use App\Http\Controllers\Api\AuthApiController;
use App\Http\Controllers\Api\AiProxyController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/dashboard/content', [DashboardApiController::class, 'content'])->name('api.dashboard.content');
Route::get('/dashboard', [DashboardApiController::class, 'content'])->name('api.dashboard.index');
Route::post('/dashboard/import', [DashboardApiController::class, 'import'])->name('api.dashboard.import');
Route::post('/dashboard/import/{batchId}/chunk', [DashboardApiController::class, 'importChunk'])->name('api.dashboard.import.chunk');
Route::post('/dashboard/import/{batchId}/finalize', [DashboardApiController::class, 'importFinalize'])->name('api.dashboard.import.finalize');
Route::get('/dashboard/import/{batchId}/status', [DashboardApiController::class, 'importStatus'])->name('api.dashboard.import.status');
Route::post('/ai/chat', [AiProxyController::class, 'chat'])->name('api.ai.chat');
Route::post('/ai/detect-intent', [AiProxyController::class, 'detectIntent'])->name('api.ai.detect_intent');
Route::post('/ai/simulation-insight', [AiProxyController::class, 'simulationInsight'])->name('api.ai.simulation_insight');
Route::post('/ai/playbook', [AiProxyController::class, 'playbook'])->name('api.ai.playbook');
// Removed generic ParameterController API; using per-model API resources below.

// API routes for master parameters (JSON CRUD)
Route::apiResource('parameters/branches', App\Http\Controllers\MstrBranchController::class)->only(['index','store','update','destroy']);
Route::apiResource('parameters/rms', App\Http\Controllers\MstrRmController::class)->only(['index','store','update','destroy']);
Route::apiResource('parameters/bi_industries', App\Http\Controllers\MstrBiIndustryController::class)->only(['index','store','update','destroy']);
Route::apiResource('parameters/cimb_sectors', App\Http\Controllers\MstrCimbSectorController::class)->only(['index','store','update','destroy']);
Route::apiResource('parameters/constitutions', App\Http\Controllers\MstrConstitutionController::class)->only(['index','store','update','destroy']);
Route::apiResource('parameters/economy_sectors', App\Http\Controllers\MstrEconomySectorController::class)->only(['index','store','update','destroy']);
Route::apiResource('parameters/bi_collectabilities', App\Http\Controllers\MstrBiCollectabilityController::class)->only(['index','store','update','destroy']);
Route::apiResource('parameters/basel_types', App\Http\Controllers\MstrBaselTypeController::class)->only(['index','store','update','destroy']);


Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Auth API
Route::post('/auth/login', [AuthApiController::class, 'login'])->name('api.auth.login');
Route::post('/auth/register', [AuthApiController::class, 'register'])->name('api.auth.register');
Route::middleware('auth:sanctum')->post('/auth/logout', [AuthApiController::class, 'logout'])->name('api.auth.logout');
