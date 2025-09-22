<?php

use Illuminate\Support\Facades\Route;
use Prasso\Church\Http\Controllers\ReportController;

Route::prefix('reports')->name('reports.')->group(function () {
    // Dashboard and report listing
    Route::get('/dashboard', [ReportController::class, 'dashboard'])->name('dashboard');
    Route::get('/types', [ReportController::class, 'getReportTypes'])->name('types');
    
    // Report management
    Route::get('/', [ReportController::class, 'index'])->name('index');
    Route::post('/', [ReportController::class, 'store'])->name('store');
    Route::get('/{report}', [ReportController::class, 'show'])->name('show');
    Route::put('/{report}', [ReportController::class, 'update'])->name('update');
    Route::delete('/{report}', [ReportController::class, 'destroy'])->name('destroy');
    
    // Report generation and export
    Route::post('/generate', [ReportController::class, 'generateCustomReport'])->name('generate');
    Route::post('/{report}/run', [ReportController::class, 'runReport'])->name('run');
    Route::get('/runs/{run}/status', [ReportController::class, 'getRunStatus'])->name('runs.status');
    Route::get('/runs/{run}/download', [ReportController::class, 'downloadReport'])->name('runs.download');
    
    // Scheduling
    Route::post('/{report}/schedules', [ReportController::class, 'scheduleReport'])->name('schedules.store');
    Route::delete('/schedules/{schedule}', [ReportController::class, 'deleteSchedule'])->name('schedules.destroy');
    
    // Export endpoints
    Route::post('/export', [ReportController::class, 'exportReport'])->name('export');
});
