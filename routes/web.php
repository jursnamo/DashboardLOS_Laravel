<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LoanApplicationController;
use Illuminate\Support\Facades\Route;

// Public Routes
Route::get('/', [AuthController::class, 'showLoginForm'])->name('login');

// Auth Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login.show');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
    Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.store');
});

// Protected Routes
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Loan Origination System (LOS)
    Route::prefix('los')->name('los.')->group(function () {
        Route::get('/applications', [LoanApplicationController::class, 'index'])->name('applications.index');
        Route::get('/monitoring', [LoanApplicationController::class, 'monitoring'])->name('monitoring.index');
        Route::post('/applications', [LoanApplicationController::class, 'store'])->name('applications.store');
        Route::get('/applications/{loanApplication}', [LoanApplicationController::class, 'show'])->name('applications.show');
        Route::put('/applications/{loanApplication}', [LoanApplicationController::class, 'update'])->name('applications.update');
        Route::post('/applications/{loanApplication}/submit', [LoanApplicationController::class, 'submit'])->name('applications.submit');
        Route::post('/applications/{loanApplication}/approve', [LoanApplicationController::class, 'approve'])->name('applications.approve');
        Route::post('/applications/{loanApplication}/reject', [LoanApplicationController::class, 'reject'])->name('applications.reject');
        Route::post('/applications/{loanApplication}/collaterals', [LoanApplicationController::class, 'addCollateral'])->name('applications.collaterals.store');
        Route::post('/applications/{loanApplication}/documents', [LoanApplicationController::class, 'upsertDocument'])->name('applications.documents.upsert');
        Route::post('/applications/{loanApplication}/covenants', [LoanApplicationController::class, 'addCovenant'])->name('applications.covenants.store');
        Route::get('/documents/{loanDocument}/download', [LoanApplicationController::class, 'downloadDocument'])->name('documents.download');
        Route::post('/approval-matrix', [LoanApplicationController::class, 'storeMatrix'])->name('approval-matrix.store');
        Route::delete('/approval-matrix/{matrix}', [LoanApplicationController::class, 'destroyMatrix'])->name('approval-matrix.destroy');
    });
    
    // Per-model parameter CRUD routes (one controller per master model)
    Route::prefix('parameters')->group(function () {
        Route::resource('branches', App\Http\Controllers\MstrBranchController::class)->except(['show','create','edit']);
        Route::resource('rms', App\Http\Controllers\MstrRmController::class)->parameters(['rms' => 'id'])->except(['show','create','edit']);
        Route::resource('bi_industries', App\Http\Controllers\MstrBiIndustryController::class)->parameters(['bi_industries' => 'id'])->except(['show','create','edit']);
        Route::resource('cimb_sectors', App\Http\Controllers\MstrCimbSectorController::class)->parameters(['cimb_sectors' => 'id'])->except(['show','create','edit']);
        Route::resource('constitutions', App\Http\Controllers\MstrConstitutionController::class)->parameters(['constitutions' => 'id'])->except(['show','create','edit']);
        Route::resource('economy_sectors', App\Http\Controllers\MstrEconomySectorController::class)->parameters(['economy_sectors' => 'id'])->except(['show','create','edit']);
        Route::resource('bi_collectabilities', App\Http\Controllers\MstrBiCollectabilityController::class)->parameters(['bi_collectabilities' => 'id'])->except(['show','create','edit']);
        Route::resource('basel_types', App\Http\Controllers\MstrBaselTypeController::class)->parameters(['basel_types' => 'id'])->except(['show','create','edit']);
    });
});
